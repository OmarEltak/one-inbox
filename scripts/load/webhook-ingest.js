// webhook-ingest.js — POST fake Meta webhook payloads to measure ingest rate.
//
// Ramp 1 → 100 req/s over 3 minutes with a hold at 100 for 30s. Baseline
// expectation from docs/OT1_LIMITS.md §5.1: ~30 req/s sustained ceiling
// (theory: 50 ms/req × 5 FPM workers).
//
// This does NOT include a valid X-Hub-Signature-256 — real prod will
// reject them at the middleware layer. That's intentional: this script
// measures the HTTP-ingest cost (nginx + FPM startup + middleware chain)
// without loading DB or spawning downstream jobs. Rejection returns 4xx
// quickly enough that we get a clean view of the FPM saturation curve.
//
// For a full pipeline test (with valid signature + DB write + queue
// dispatch), we'd need to sign each payload with the real META_APP_SECRET
// — do NOT do that against prod.
//
// Run:
//   k6 run scripts/load/webhook-ingest.js -e TARGET=https://staging.ot1-pro.com
//
// DO NOT run against prod during business hours.

import http from 'k6/http';
import { check } from 'k6';
import { Rate } from 'k6/metrics';

const TARGET = __ENV.TARGET || 'http://127.0.0.1:8765';

export const errorRate = new Rate('errors');

export const options = {
    stages: [
        { duration: '30s',  target: 10 },
        { duration: '60s',  target: 50 },
        { duration: '60s',  target: 100 }, // ramp to 100 VUs
        { duration: '30s',  target: 100 }, // hold peak
        { duration: '30s',  target: 0 },
    ],
    thresholds: {
        errors:            ['rate<0.10'], // 10% budget — many will legitimately 400 on signature
        http_req_duration: ['p(95)<3000'],
    },
};

const FAKE_PAYLOAD = JSON.stringify({
    object: 'page',
    entry: [{
        id:   '1234567890',
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
            'User-Agent':          'k6-loadtest (ot1-pro webhook-ingest.js)',
        },
        tags: { endpoint: 'webhook_meta' },
    });

    // 4xx is expected (invalid signature). 5xx is a real failure.
    const ok = check(res, {
        'no 5xx':          (r) => r.status < 500,
        'responds < 3s':   (r) => r.timings.duration < 3000,
    });

    errorRate.add(!ok);
    // No sleep — this measures peak ingest, not think-time.
}
