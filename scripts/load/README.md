# Load Testing — k6 scripts

Scripts for empirically measuring the capacity ceilings documented in `docs/OT1_LIMITS.md §5.7`.

## Install k6

Windows: `winget install k6`
Mac: `brew install k6`
Linux: `sudo apt install k6` (after adding the InfluxData repo)

## Run

Single script against a target:

```bash
k6 run scripts/load/homepage.js -e TARGET=https://staging.ot1-pro.com
```

Every script accepts `-e TARGET=<url>` so you can point at `http://127.0.0.1:8765`, a staging host, or prod (**never prod during business hours**).

## Scripts (priority order per doc §8)

| # | Script | What it exercises | Expected ceiling |
|---|---|---|---|
| 1 | `webhook-ingest.js` | POST `/api/webhooks/meta` with signed fake payloads, ramp 1 → 100 req/s | ~30 req/s sustained (5 FPM workers × 50ms/req) |
| 2 | `homepage.js` | GET `/` at ramp 50 → 500 req/s | ~25 req/s sustained before 504 spike |
| 3 | `campaign-parse-storm.js` | Upload N 5-MB CSVs simultaneously (skipped — needs authenticated fixture) | 5 concurrent (FPM cap) |

## Interpreting results

k6 emits p50 / p95 / p99 latency + error rate. Compare against the "Expected ceiling" above. When measurements settle, update the `(theory)` marks in `docs/OT1_LIMITS.md §5.7` to `(measured YYYY-MM-DD)` in the same commit as the k6 report.

Save reports as JSON:

```bash
k6 run scripts/load/homepage.js --out json=docs/load-tests/YYYY-MM-DD-homepage.json
```

## Warning

**Do NOT run these against prod during business hours.** The webhook-ingest script alone can saturate PHP-FPM within seconds and take the entire site offline for real customers. Spin up a `staging.ot1-pro.com` mirror (Hetzner CX22 ≈ $5/mo) — see doc §12 for the deploy pipeline you can point at it.
