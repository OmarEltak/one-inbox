// webhook-ingest-breakpoint.js — push webhook ingest to find the actual breakpoint.
//
// Previous run (2026-10-18, 100 VUs) found 94 req/s sustained with p95=1.01s
// and 0% 5xx — box held. This script ramps 50 → 300 VUs to find the real knee.
//
// All payloads carry an invalid HMAC signature, so each request takes the fast
// 4xx reject path. Measures FPM + nginx entry cost only; no DB writes, no job
// dispatch. Safe to run against prod.
//
// Run:
//   k6 run scripts/load/webhook-ingest-breakpoint.js -e TARGET=https://ot1-pro.com

import http from 'k6/http';
import { check } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const TARGET = __ENV.TARGET || 'https://ot1-pro.com';

export const errorRate   = new Rate('errors');
export const serverError = new Rate('server_5xx');
export const latency504  = new Trend('latency_at_504');

export const options = {
    stages: [
        { duration: '30s', target:  50 },
        { duration: '60s', target: 150 },
        { duration: '60s', target: 300 },
        { duration: '60s', target: 300 },  // hold peak
        { duration: '30s', target:   0 },
    ],
    // No threshold-fails — we WANT to see where it breaks.
};

const FAKE_PAYLOAD = JSON.stringify({
    object: 'page',
    entry: [{
        id: '1234567890',
        time: Math.floor(Date.now() / 1000),
        messaging: [{
            sender:    { id: 'user_' + Math.floor(Math.random() * 1_000_000) },
            recipient: { id: '1234567890' },
            timestamp: Math.floor(Date.now() / 1000) * 1000,
            message:   { mid: 'mid_' + Math.random(), text: 'load-test payload' },
        }],
    }],
});

export default function () {
    const res = http.post(`${TARGET}/api/webhooks/meta`, FAKE_PAYLOAD, {
        headers: {
            'Content-Type':        'application/json',
            'X-Hub-Signature-256': 'sha256=fake_signature_intentionally_invalid',
            'User-Agent':          'k6-loadtest (ot1-pro webhook-ingest-breakpoint.js)',
        },
        tags: { endpoint: 'webhook_meta' },
    });

    const is5xx = res.status >= 500;
    serverError.add(is5xx);
    if (is5xx) latency504.add(res.timings.duration);

    const ok = check(res, {
        'no 5xx':         (r) => r.status < 500,
        'responds < 10s': (r) => r.timings.duration < 10000,
    });
    errorRate.add(!ok);
}
