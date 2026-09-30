// homepage.js — GETs / to measure marketing-landing-traffic capacity.
//
// Simulates a marketing burst: ramp 50 → 500 req/s over 2 minutes, hold at
// 500 for 30 seconds, ramp down. Baseline expectation from
// docs/OT1_LIMITS.md §5.7: ~25 req/s sustained before 504s spike.
//
// Run:
//   k6 run scripts/load/homepage.js -e TARGET=https://staging.ot1-pro.com
//
// DO NOT run against prod during business hours.

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const TARGET = __ENV.TARGET || 'http://127.0.0.1:8765';

export const errorRate = new Rate('errors');

export const options = {
    stages: [
        { duration: '30s', target: 50 },   // warm up
        { duration: '90s', target: 500 },  // ramp to 500 concurrent virtual users
        { duration: '30s', target: 500 },  // hold peak
        { duration: '30s', target: 0 },    // ramp down
    ],
    thresholds: {
        // Fail the run if error rate > 5% or p95 latency > 5s.
        errors:              ['rate<0.05'],
        http_req_duration:   ['p(95)<5000'],
    },
};

export default function () {
    const res = http.get(`${TARGET}/`, {
        headers: {
            'User-Agent': 'k6-loadtest (ot1-pro homepage.js)',
            'Accept':     'text/html',
        },
        tags: { endpoint: 'home' },
    });

    const ok = check(res, {
        'status 200':                 (r) => r.status === 200,
        'has "OT1-Pro" in body':      (r) => r.body && r.body.includes('OT1-Pro'),
    });

    errorRate.add(!ok);

    // Simulate real user think-time — a bot pounding as fast as possible
    // is not the traffic pattern we care about. Real marketing visitors
    // spend seconds on the page.
    sleep(2 + Math.random() * 3);
}
