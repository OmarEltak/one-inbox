// marketing-pages-mix.js — simulates a realistic mix of marketing-page traffic.
//
// 50 % homepage, 25 % random blog post, 15 % /pricing, 10 % /vs/wati.
// Ramps 50 → 300 VUs over 4 minutes with 60 s hold.
//
// This is the "SEO link lands on Hacker News / Reddit" scenario — mixed read
// traffic hitting the full page cache + Blade render + related-posts queries.
//
// Run:
//   k6 run scripts/load/marketing-pages-mix.js -e TARGET=https://ot1-pro.com

import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const TARGET = __ENV.TARGET || 'https://ot1-pro.com';

export const errorRate      = new Rate('errors');
export const serverError5xx = new Rate('server_5xx');
export const blogLatency    = new Trend('blog_latency', true);
export const pricingLatency = new Trend('pricing_latency', true);
export const homeLatency    = new Trend('home_latency', true);

export const options = {
    stages: [
        { duration: '30s', target:  50 },
        { duration: '90s', target: 150 },
        { duration: '60s', target: 300 },
        { duration: '60s', target: 300 },
        { duration: '30s', target:   0 },
    ],
};

// Real published slugs sampled from prod on 2026-10-18.
const BLOG_SLUGS = [
    'bot-mabee3at-yebee3-badal-mowazafeen',
    'moqarna-doradsha-hayya-vs-bareed-electronic-edarat-mohadathat',
    'inbound-marketing-email-system-integration',
    'atmata-wel-rudood-el-gahiza-edarat-mohadathat',
    'salon-no-shows-whatsapp-reminder-ai-cut-cancellations',
    'meta-app-verification-2026-founder-guide',
];

function pick(arr) { return arr[Math.floor(Math.random() * arr.length)]; }

export default function () {
    const roll = Math.random();
    let url, latencyMetric;

    if (roll < 0.50) {
        url = `${TARGET}/`;
        latencyMetric = homeLatency;
    } else if (roll < 0.75) {
        url = `${TARGET}/blog/${pick(BLOG_SLUGS)}`;
        latencyMetric = blogLatency;
    } else if (roll < 0.90) {
        url = `${TARGET}/pricing`;
        latencyMetric = pricingLatency;
    } else {
        url = `${TARGET}/vs/wati`;
        latencyMetric = pricingLatency;
    }

    const res = http.get(url, {
        headers: {
            'User-Agent': 'k6-loadtest (ot1-pro marketing-pages-mix.js)',
            'Accept':     'text/html,application/xhtml+xml',
        },
        tags: { endpoint: url.replace(TARGET, '') },
    });

    const is5xx = res.status >= 500;
    serverError5xx.add(is5xx);
    latencyMetric.add(res.timings.duration);

    const ok = check(res, {
        'no 5xx':         (r) => r.status < 500,
        'has body':       (r) => r.body && r.body.length > 500,
    });
    errorRate.add(!ok);

    sleep(1 + Math.random() * 2);  // 1-3 s think time
}
