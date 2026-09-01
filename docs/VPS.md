# Production VPS Reference

> Snapshot of the ot1-pro.com production server. Verified 2026-09-01.
> Update this file whenever the server topology, installed software, or resource envelope changes materially.

## Access

| Field | Value |
|---|---|
| Host | `187.77.67.94` |
| SSH | `ssh root@187.77.67.94` |
| App path | `/var/www/ot1-pro.com` |
| Domain | `https://ot1-pro.com` |
| App owner (files) | `deploy:www-data` |

## Hardware

| Resource | Amount | Notes |
|---|---|---|
| CPU | 2 cores | Tight — CPU-bound work (whisper.cpp, image resize, sync AI calls) contends with PHP-FPM + queue workers. |
| RAM | 7.8 GB total / 6.4 GB available | Comfortable headroom. Swap = 0 (never enabled). |
| Disk | 96 GB / 91 GB free | Plenty for user-uploaded media at current scale. |

## OS & Runtimes

| Component | Version |
|---|---|
| OS | Ubuntu 24.04.4 LTS |
| Web server | nginx 1.24.0 |
| PHP | 8.4.23 (FPM) |
| MySQL | 8.0.46 |
| Redis | 7.0.15 |
| Node | 18.19.1 |
| ffmpeg | not installed |
| whisper.cpp | not installed |

## Running systemd services

- `nginx.service`
- `php8.4-fpm.service`
- `mysql.service`
- `redis-server.service`
- `one-inbox-queue@{1,2,3,4}.service` — 4 parallel queue workers
- `one-inbox-reverb.service` — WebSocket server on port 8080

## Environment

Prod `.env` lives at `/var/www/ot1-pro.com/.env` (mode 600, not in git). Edit via SSH, then:

```bash
sudo -u deploy php artisan config:cache && systemctl reload php8.4-fpm
```

⚠️ **Never run `config:cache` as root** — see memory `feedback_prod_config_cache_user.md`. It has caused MissingAppKeyException 500s for www-data-served requests.

## Deploy flow

Push to `main` → GitHub Actions auto-deploys in ~24s. Seeders do NOT run automatically. Manual run:

```bash
ssh root@187.77.67.94 "cd /var/www/ot1-pro.com && sudo -u deploy php artisan db:seed --class='Database\\Seeders\\...' --force"
```

## AI provider — NaraRouter (as of 2026-09-01)

| Setting | Value |
|---|---|
| Primary | `agnes-2.5-flash` (Gemini 2.5 Flash alias — Vision-capable, 512K ctx) |
| Scoring | `ox-alpha-bynara` |
| Fallback chain | `agnes-2.5-flash → agnes-2.0-flash → nemotron-3-ultra → qwen-3.8-max-free → deepseek-v4-flash → mistral-large` |
| Reset window | 5 hours |
| Alert email | omareltak7@gmail.com |

**Capability caveats** (verified from NaraRouter dashboard 2026-09-01):

- **No model in the chain accepts audio input.** Audio requires a self-hosted transcription step (whisper.cpp) before hitting NaraRouter.
- Vision support in the current chain: `agnes-2.5-flash` ✅, `agnes-2.0-flash` ✅, tail models (`deepseek-v4-flash`, `mistral-large`) ❌ text-only.
- Some fallback aliases in the env (`nemotron-3-ultra`, `qwen-3.8-max-free`) don't appear in the current free-model catalog — may be stale. Verify via `/v1/models` before assuming they resolve.

See `docs/ARCHITECTURE.md` §4 for the failover-reset semantics and the `nararouter-ops` skill for any changes to this config.

## Resource-planning rules of thumb

- **CPU-heavy background jobs** (whisper.cpp, image transcode): keep concurrency at 1 to avoid starving PHP-FPM. Consider a dedicated queue + a single worker.
- **RAM budget for new services**: safe to add up to ~2 GB of steady-state usage without risking swap (there is none).
- **Disk growth**: user-uploaded media is currently 0 bytes. Budget conservatively — WhatsApp voice notes average ~50 KB/sec (~3 MB/min), images ~200 KB. 10K messages/mo with media = ~2 GB/mo. At 91 GB free, ~45 months of runway before we need object storage.
