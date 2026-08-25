# One Inbox — Cloud Deployment Plan

> Companion to `deployment-architecture.md` (current single-laptop design).
> This document is the target: **single-server** cloud on Hostinger + Laravel Forge, with the laptop demoted to a dev box.
> Status: **design only — not yet provisioned.**
> Last updated: 2026-07-06
>
> **Payment note (2026-07-06):** Provider selection is constrained to services whose checkouts accept **Apple Pay** (via Stripe). This is why the plan uses **Hostinger + Forge + Cloudflare R2** instead of Hetzner/DO/Hetzner Object Storage — those don't accept Apple Pay. Signups must be done from iPhone Safari, since the Apple Pay button doesn't render in Chrome on Windows.
>
> **Scope note (2026-07-06):** WhatsApp gateways (Evolution + Wuzapi) are out of scope for this cutover. Any WhatsApp-specific webhook wiring is deferred. When WhatsApp comes back on the roadmap, reintroduce it as **Phase C6** — provision a second small VPS (Hostinger KVM 1), deploy the Docker compose stacks, restore the encryption-key backup ritual. Until then, WhatsApp keeps running on the laptop (or is simply off).

---

## Stack at a glance

The short version: **one Hostinger VPS + self-managed nginx/systemd + Cloudflare Free + Cloudflare R2, ~$9/mo total.** No recurring SaaS tool subscription — we configure the server manually (one-time setup). Section 2 has the full reasoning; this is the table to send to anyone who asks "what's the plan?"

| Layer | Service | Cost | Why this and not the alternative |
|---|---|---|---|
| **App server** | Hostinger **KVM 2** (Amsterdam, NL) — 2 vCPU / 8 GB / 100 GB NVMe, Ubuntu 24.04 | **~$8/mo** (1-yr commit) | Accepts Apple Pay at checkout. 8 GB RAM matches the Hetzner spec we would have picked; 2 vCPU is enough for Laravel + queue workers + Reverb at Phase-2 traffic. Amsterdam → Cairo is ~85 ms. |
| **Server management** | **Self-managed** (nginx + PHP-FPM + systemd + certbot + GitHub Actions) | **$0** | Forge was blocked by payment constraints. Manual setup is a one-time ~2–3 hour investment. Every piece is standard Ubuntu tooling with no recurring fee. Auto-deploy via a GitHub Actions SSH workflow replaces the Forge deploy button. |
| **DNS + DDoS + WAF + TLS** | **Cloudflare Free** | **$0** | Already in use. Free tier includes generous Cache Rules / Configuration Rules (enough for webhook bypass + static asset caching). Free TLS, free DDoS, free DNS. WSS supported by default. |
| **Object storage** | **Cloudflare R2** (S3-compatible) | **$0–$1/mo** | Same Cloudflare account, Apple Pay ✅. 10 GB storage + 1M writes + 10M reads free per month — likely covers us for months. **Zero egress fees** (unlike AWS S3), which matters if we ever serve customer media directly. |
| **Database** | **MySQL 8** (Forge-managed, runs on Hostinger VPS) | **$0** (bundled) | Forge's default install. We have no Postgres-specific features (no PostGIS / JSONB / arrays), and MySQL 8 handles JSON well enough. |
| **Cache + queue + session + broadcasting** | **Redis 7** (Forge-managed, runs on Hostinger VPS) | **$0** (bundled) | Replaces the current `database` cache/queue/session drivers. Bundled on the same server as MySQL. |
| **WebSockets (Reverb)** | Same VPS, separate PHP process managed by Forge as a daemon | **$0** | In-process with nginx → PHP-FPM. No separate box needed. |
| **Domain** | `ot1-pro.com` (existing) | **~$1/mo** (already owned) | Customers already have the URL; SSL cert history is clean. No need to introduce a new domain at cloud cutover. |
| **AI provider** | NaraRouter → Anthropic / Gemini | **variable** (~$0.0004/response) | Already wired in `config/services.php`. No infra change — pay per token. |
| | | | |
| **Total steady state** | | **~$9/mo** | Hostinger KVM 2 (~£6.79/$8) + Cloudflare R2 ($0–1). No Forge subscription. WhatsApp gateway deferred. Cheapest viable production stack possible. |

### What we explicitly do NOT pay for

- **Kubernetes** — 5× cost and 100× ops overhead at this scale. K8s becomes relevant past ~10 app servers.
- **Multi-region** — single region (Amsterdam, NL) is fine for a MENA customer base.
- **Redis Cluster / Sentinel** — a single Redis can survive a restart; the queue backs up but doesn't lose data once `appendonly yes` is set.
- **Horizontal app scaling** — vertical first (KVM 2 → KVM 4 → KVM 8). Horizontal needs sticky sessions, replicated MySQL, and Redis Sentinel. That's a 2027 problem.
- **Second app server for HA** — deferred until monthly revenue crosses ~$2k. One prod server + a tested restore procedure is the right move for the next 6 months.
- **Penetration testing / compliance audits** — out of scope at this stage; revisit pre-Enterprise.
- **Mobile app deployment** — Phase 7 of `launch-plan.md`, separate plan.

### What we are giving up

- **No more "git pull on the laptop" deploys.** Pushes go to GitHub → Forge deploys to the Hostinger VPS automatically.
- **No more SQLite.** MySQL 8 from Forge.
- **No more NSSM.** systemd units installed by Forge.
- **No more cloudflared on the laptop.** Cloudflare now fronts the Hostinger VPS. The laptop uses `one-inbox.test` (Herd) only.
- **The 65 MB `cloudflared.exe` binary in the repo root** is deleted after cutover — it was a launch-plan Phase 0 artifact.

---

## 0. Why a cloud plan at all

The current `deployment-architecture.md` runs prod on the laptop. That's fine for "show it to a customer" but cannot survive:

- A reboot that crashes before the NSSM services recover (we've already missed `OneInboxSchedulerProd` in the current `deploy.ps1`)
- A failing laptop drive (SQLite is a single file, 41 MB, no replication)
- A second customer hitting the same webhook path before the first customer's request is processed — Reverb is a single process
- Meta App Review, which requires a stable production URL we can demonstrate

Cloud = a single server that *we* operate (laptop stays dev), with the option to grow to two when revenue justifies the second machine.

---

## 1. Topology — what we are building

### 1.1 Target architecture (steady state)

```
                          ┌──────────────────────────┐
                          │  Cloudflare (free)       │
                          │  DNS + DDoS + WAF + TLS  │
                          │  ot1-pro.com, app.ot1... │
                          └──────────┬───────────────┘
                                     │ outbound tunnel
                                     ▼
                          ┌──────────────────────────┐
                          │  Hostinger KVM 2 (AMS)   │  ← "prod"
                          │  Ubuntu 24.04 LTS        │
                          │  2 vCPU / 8 GB / 100 GB  │
                          │  ~$8/mo                  │
                          │  managed via Forge as    │
                          │  a Custom VPS over SSH   │
                          │                          │
                          │  ┌────────────────────┐  │
                          │  │ nginx (Forge)      │  │
                          │  │  + Let's Encrypt   │  │
                          │  └─────────┬──────────┘  │
                          │            │             │
                          │  ┌─────────▼──────────┐  │
                          │  │ PHP 8.4-FPM        │  │
                          │  │ Laravel app        │  │
                          │  └─────────┬──────────┘  │
                          │            │             │
                          │  ┌─────────▼──────────┐  │
                          │  │ 127.0.0.1          │  │
                          │  │  ┌──────────────┐  │  │
                          │  │  │ MySQL 8      │  │  │
                          │  │  │ Redis 7      │  │  │
                          │  │  └──────────────┘  │  │
                          │  └────────────────────┘  │
                          │                          │
                          │  systemd:                │
                          │   oneinbox-queue.service │
                          │   oneinbox-reverb.service│
                          │   oneinbox-scheduler.svc │
                          └──────────────────────────┘

                                   Cloudflare R2 (S3-compatible)
                              ←S3→  Backups + media
                                    $0–$1/mo (10 GB free tier)
```

> Deferred: a small second VPS (Hostinger KVM 1 ~ $5/mo) for WhatsApp gateways (Evolution + Wuzapi). Not part of this cutover — see scope note at top.

### 1.2 What we are NOT building

- **No Kubernetes.** One server, managed by Forge. K8s is a 5× cost increase and 100× ops overhead at this scale.
- **No multi-region.** Customers are Egypt/MENA, single region is fine.
- **No separate Redis Cluster.** A single Redis instance on the Hostinger VPS handles cache + queue + session + broadcasting. If it dies, queue backs up, but we can survive that.
- **No horizontal app scaling.** Vertical scale (KVM 2 → KVM 4 → KVM 8) comes first. Horizontal needs sticky sessions, replicated MySQL, and Redis Sentinel. That's a 2027 problem.

### 1.3 Webhook path (the most fragile part of this design)

Every Meta, Telegram, TikTok, Snapchat, Slack, Discord, and Stripe request lands on `/api/webhooks/*`. They all expect:

- A **stable URL** that doesn't change
- A **valid TLS certificate** (Meta rejects self-signed)
- **Low latency response** (Meta's webhook SLA: <5s, 200 OK or it retries)
- **Verify-token handshake** (`META_WEBHOOK_VERIFY_TOKEN` for Meta, equivalent for others)

Forge + Let's Encrypt gives us all of that. Cloudflare sits in front as a CDN, but **webhook traffic must reach origin, not be cached or interrupted** — we'll add a Cloudflare Page Rule bypassing cache for `/api/webhooks/*`.

---

## 2. Stack choices — the deep reasoning

The "Stack at a glance" table at the top lists the services and prices. This section expands on the choices that have real trade-offs and would survive a "why not the other thing?" question.

### 2.1 Why Hostinger VPS, not Hetzner / DigitalOcean / Vultr / AWS

Hostinger is not the cheapest per-vCPU or the most "reference-grade" cloud provider. It wins here for exactly one reason: **their checkout accepts Apple Pay via Stripe**. That's a hard constraint (see payment note at top).

- **Cost** — KVM 2 at ~$8/mo (1-yr commit) is actually **cheaper** than Hetzner CX32 (~$11/mo) for a similar-workload spec, though Hetzner would give us 4 vCPU instead of 2 for that price. At Phase-2 traffic (a few hundred conversations/day), 2 vCPU is enough.
- **Latency to MENA** — Amsterdam → Cairo is ~85 ms. Basically identical to Falkenstein (~80 ms). No user will notice.
- **EU data residency** — Amsterdam is EU. Same GDPR posture as Hetzner Falkenstein.
- **Throughput** — Hostinger includes 8–32 TB of monthly bandwidth per KVM plan. More than enough.
- **Trade-off** — Hostinger's uptime SLA is less battle-tested than Hetzner's or DigitalOcean's. If we hit revenue where downtime hurts, we swap the underlying VPS (Forge makes this ~1 hour of work) to whichever provider *then* accepts Apple Pay (or by then we may have unlocked other payment methods).
- **Alternatives that also take Apple Pay** — Fly.io (~$15–25/mo, no Forge, deploys via CLI), Railway (~$20/mo, PaaS), Render (~$33/mo, most expensive). All rewrite the deploy story. Hostinger + Forge preserves the plan verbatim.

### 2.2 Why self-managed, not Forge / Ploi / RunCloud

Forge ($12/mo) and all alternatives (Ploi $10/mo, RunCloud $8–15/mo) use Stripe for billing. With Fawry as the only payment method and no working virtual card, every managed-panel option is blocked.

**What self-managed means concretely** — we replicate what Forge would have done, once, via SSH:

| Forge feature | Self-managed equivalent |
|---|---|
| nginx vhost + PHP-FPM | Write `/etc/nginx/sites-available/ot1-pro.com` |
| Let's Encrypt + auto-renew | `certbot --nginx`, auto-renew via systemd timer |
| Queue worker daemon | `/etc/systemd/system/oneinbox-queue.service` |
| Reverb daemon | `/etc/systemd/system/oneinbox-reverb.service` |
| Scheduler | `crontab -e` → `* * * * * php artisan schedule:run` |
| UFW firewall | `ufw allow 80,443,22/tcp` |
| GitHub auto-deploy | `.github/workflows/deploy.yml` → SSH + deploy script |

**Time cost:** ~2–3 hours of setup once, then zero ongoing maintenance beyond `apt upgrade`.
**Money cost:** $0/mo forever.

**Trade-off** — no web dashboard. Restarts, log tailing, and env edits require SSH. Acceptable for a solo/small team at this stage. If Forge becomes payable later (Fawry+ virtual card, etc.), migration is trivial — Forge can provision on an already-running server.

### 2.3 Why Cloudflare Free, not Cloudflare Pro ($20/mo) / Bunny.net / Fastly

- **Free tier includes** — unlimited DDoS protection, free TLS, free DNS, generous Cache Rules / Configuration Rules quotas, basic WAF managed rules
- **Pro adds** — advanced WAF, image optimization, mobile optimization, more Cache Rules
- **What we actually need** — a handful of Cache Rules covering: (1) webhook cache bypass on `/api/webhooks/*`, (2) static asset caching on `/build/*`, (3) www→apex redirect. All fit comfortably in Free.
- **Bunny.net** ($1/mo + bandwidth) is technically cheaper but doesn't include DDoS protection or DNS, so the total is similar with more setup.

### 2.3b Why Cloudflare R2, not Hetzner Object Storage / Backblaze B2 / AWS S3

- **Payment constraint** — R2 bills through Cloudflare, which uses Stripe → Apple Pay ✅. Hetzner Object Storage bills through Hetzner (no Apple Pay). AWS S3 also no Apple Pay.
- **Free tier** — 10 GB storage, 1M writes/mo, 10M reads/mo. We'll likely stay in it for months.
- **Zero egress fees** — R2's headline feature. If we ever serve customer media directly to browsers, we don't pay per-GB egress the way S3/Hetzner Storage would charge (Hetzner is ~$1/TB egress; AWS is ~$90/TB). This matters more the bigger we get.
- **S3-compatible API** — Laravel's `Storage::disk('s3')` works unmodified. Just point `AWS_ENDPOINT` at R2.
- **Trade-off** — R2 has been generally available since 2022, less battle-tested than S3, but stable enough for our media/backup use case. Backups are already redundant (Forge holds a copy locally on the VPS for 7 days).

### 2.4 Why MySQL 8, not Postgres

- **Forge installs MySQL 8 by default.** Switching to Postgres means a custom install and one more thing to maintain.
- **No Postgres-specific features in use** — we use Eloquent's JSON columns (both DBs support them), no array types, no `RETURNING` chains, no `LISTEN/NOTIFY`.
- **Migrations are portable** — all our migrations use Eloquent schema builder syntax that works on both.
- **MySQL 8 has `utf8mb4_0900_ai_ci` as default** — same Unicode handling as modern Postgres.
- **Trade-off** — if we later want full-text search on conversations, Postgres `tsvector` is better than MySQL `FULLTEXT`. At that point we'd run Postgres alongside MySQL, not replace it.

### 2.5 Why no CDN for the main app (yet)

- Forge + Cloudflare orange-cloud proxy already gives us Cloudflare's edge cache for static assets (`/build/*`, `/storage/*`) once we add the Page Rule.
- We don't have a heavy marketing site that needs edge rendering.
- The "marketing site" is part of the Laravel app right now. If we split it out to a static site (Cloudflare Pages, ~$0), that becomes the edge story. Not now.

---

## 3. Phased rollout

The launch-plan.md `Phase 0 → Phase 2` jumps straight from "laptop as prod" to "cloud + Forge as prod." That's too big a step. Split it.

### Phase C0 — Pre-flight (1–2 days, $0 cost)

Pre-flight is about removing the things that block us. Do these in order, on the laptop:

- [ ] **Complete launch-plan.md Phase 0** — `cloudflared service install`, `APP_DEBUG=false`, daily SQLite backup, `META_WEBHOOK_VERIFY_TOKEN` rotation
- [ ] **Delete `one-inbox-prod/` directory** (the launch-plan.md open todo)
- [ ] **Regenerate `.env.example` to be complete** — current example ends at WUZAPI_QR_ENABLED but `config/services.php` has 50+ more keys. Run a script:
  ```bash
  # Generate .env.example from config/ + actual .env keys
  # Add: GEMINI_*, AI_PROVIDER, OLLAMA_*, NARAROUTER_*, TIKTOK_*, SNAPCHAT_*,
  #       LEMONSQUEEZY_*, RESEND_*, POSTMARK_*, SLACK_*, CASHIER_*, SESSION_ENCRYPT,
  #       SLOW_QUERY_*, REVERB_SERVER_*, REVERB_SCALING_*
  ```
- [ ] **Decide MySQL vs Postgres.** Recommend MySQL 8 (Forge default, less ops).
- [ ] **Confirm domain ownership** — `ot1-pro.com` is in `.env`. If we want a different domain for cloud (`one-inbox.app`?), buy it now. The current `tunnel.conf` references `one-inbox-prod.test` which must be removed in this phase.
- [ ] **Add a Reverb NSSM service for prod on the laptop** — currently missing per `setup-services.ps1` audit. This unblocks Meta App Review on `ot1-pro.com` while we build cloud.

### Phase C1 — Server build (self-managed, ~1 day, ~$0 additional cost)

The Hostinger VPS (`187.77.67.94`, KVM 2, Ubuntu 24.04) is already provisioned. This phase SSHes in and configures everything manually.

#### C1.1 — Initial server hardening

- [ ] **SSH in as root**: `ssh root@187.77.67.94`
- [ ] **Update packages**: `apt update && apt upgrade -y`
- [ ] **Install stack**:
  ```bash
  apt install -y nginx mysql-server redis-server php8.4-fpm php8.4-cli php8.4-mbstring \
    php8.4-xml php8.4-bcmath php8.4-curl php8.4-zip php8.4-mysql php8.4-redis \
    php8.4-intl php8.4-gd nodejs npm git certbot python3-certbot-nginx ufw
  ```
- [ ] **Add deploy user** (don't run the app as root):
  ```bash
  adduser deploy --disabled-password
  usermod -aG www-data deploy
  ```
- [ ] **Add your SSH public key** to the deploy user:
  ```bash
  mkdir -p /home/deploy/.ssh
  echo "ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAIIbn2Nnb11Ky8f+2+3nLbVqZIvM5ZP+OKakumhBXlSTX omar@github" \
    >> /home/deploy/.ssh/authorized_keys
  chown -R deploy:deploy /home/deploy/.ssh && chmod 700 /home/deploy/.ssh
  ```
- [ ] **UFW firewall**:
  ```bash
  ufw allow 22 && ufw allow 80 && ufw allow 443 && ufw allow 8080 && ufw enable
  ```

#### C1.2 — App setup

- [ ] **Clone the repo**:
  ```bash
  su - deploy
  git clone git@github.com:OmarEltak/one-inbox.git /var/www/ot1-pro.com
  cd /var/www/ot1-pro.com && composer install --no-dev --optimize-autoloader
  npm ci && npm run build
  ```
- [ ] **Create `.env`** from the laptop's production values (scp, not git):
  ```bash
  scp .env deploy@187.77.67.94:/var/www/ot1-pro.com/.env
  ```
  Key values to set:
  ```
  APP_ENV=production
  APP_DEBUG=false
  APP_URL=https://ot1-pro.com
  DB_CONNECTION=mysql
  CACHE_STORE=redis
  QUEUE_CONNECTION=redis
  SESSION_DRIVER=redis
  BROADCAST_CONNECTION=reverb
  REVERB_HOST=0.0.0.0
  REVERB_PORT=8080
  REVERB_SCHEME=http
  FILESYSTEM_DISK=s3
  AWS_BUCKET=one-inbox-prod
  AWS_ENDPOINT=https://<account-id>.r2.cloudflarestorage.com
  AWS_DEFAULT_REGION=auto
  AWS_USE_PATH_STYLE_ENDPOINT=true
  ```
- [ ] **MySQL setup**:
  ```bash
  mysql -u root -e "CREATE DATABASE one_inbox; CREATE USER 'deploy'@'localhost' IDENTIFIED BY 'STRONG_PASS'; GRANT ALL ON one_inbox.* TO 'deploy'@'localhost';"
  ```
- [ ] **Run migrations**: `php artisan migrate --force && php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] **Storage permissions**: `chown -R deploy:www-data storage bootstrap/cache && chmod -R 775 storage bootstrap/cache`

#### C1.3 — nginx + Let's Encrypt

- [ ] **Create nginx vhost** `/etc/nginx/sites-available/ot1-pro.com`:
  ```nginx
  server {
      listen 80;
      server_name ot1-pro.com www.ot1-pro.com;
      root /var/www/ot1-pro.com/public;
      index index.php;

      location / { try_files $uri $uri/ /index.php?$query_string; }

      location ~ \.php$ {
          fastcgi_pass unix:/run/php/php8.4-fpm.sock;
          fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
          include fastcgi_params;
      }

      location ~ /\.(?!well-known).* { deny all; }
  }
  ```
- [ ] `ln -s /etc/nginx/sites-available/ot1-pro.com /etc/nginx/sites-enabled/`
- [ ] **Let's Encrypt**: `certbot --nginx -d ot1-pro.com -d www.ot1-pro.com` (auto-configures HTTPS and sets up auto-renew timer)

#### C1.4 — systemd daemons

- [ ] **Queue worker** `/etc/systemd/system/oneinbox-queue.service`:
  ```ini
  [Unit]
  Description=One Inbox Queue Worker
  After=network.target

  [Service]
  User=deploy
  WorkingDirectory=/var/www/ot1-pro.com
  ExecStart=/usr/bin/php artisan queue:work --sleep=3 --tries=3 --max-time=3600
  Restart=always
  RestartSec=5

  [Install]
  WantedBy=multi-user.target
  ```
- [ ] **Reverb** `/etc/systemd/system/oneinbox-reverb.service`:
  ```ini
  [Unit]
  Description=One Inbox Reverb WebSocket
  After=network.target

  [Service]
  User=deploy
  WorkingDirectory=/var/www/ot1-pro.com
  ExecStart=/usr/bin/php artisan reverb:start --port=8080
  Restart=always
  RestartSec=5

  [Install]
  WantedBy=multi-user.target
  ```
- [ ] **Scheduler** (crontab for deploy user): `crontab -u deploy -e` → add:
  ```
  * * * * * cd /var/www/ot1-pro.com && php artisan schedule:run >> /dev/null 2>&1
  ```
- [ ] `systemctl enable --now oneinbox-queue oneinbox-reverb`

#### C1.5 — GitHub Actions auto-deploy

- [ ] **Add GitHub Secret** `DEPLOY_KEY` = contents of `~/.ssh/id_ed25519` (the private key)
- [ ] **Add GitHub Secret** `DEPLOY_HOST` = `187.77.67.94`
- [ ] **Create `.github/workflows/deploy.yml`**:
  ```yaml
  name: Deploy to Production

  on:
    push:
      branches: [main]

  jobs:
    deploy:
      runs-on: ubuntu-latest
      steps:
        - uses: actions/checkout@v4

        - name: Deploy via SSH
          uses: appleboy/ssh-action@v1
          with:
            host: ${{ secrets.DEPLOY_HOST }}
            username: deploy
            key: ${{ secrets.DEPLOY_KEY }}
            script: |
              cd /var/www/ot1-pro.com
              git pull origin main
              composer install --no-dev --optimize-autoloader
              npm ci && npm run build
              php artisan migrate --force
              php artisan config:cache
              php artisan route:cache
              php artisan view:cache
              php artisan queue:restart
              sudo systemctl reload php8.4-fpm
  ```
- [ ] **Give deploy user passwordless sudo for php-fpm reload only**:
  ```bash
  echo "deploy ALL=(ALL) NOPASSWD: /bin/systemctl reload php8.4-fpm" >> /etc/sudoers.d/deploy
  ```

#### C1.6 — Cloudflare + webhooks

- [ ] **Set Cloudflare DNS**: `ot1-pro.com` → `187.77.67.94` (A record, proxied ✅)
- [ ] **Set SSL/TLS to "Full (Strict)"** in Cloudflare dashboard
- [ ] **Add Cache Rule**: `*ot1-pro.com/api/webhooks/*` → Cache Level = Bypass
- [ ] **Create Cloudflare R2 bucket** `one-inbox-prod`, generate R2 API token, add `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` to `.env`
- [ ] **Update webhook URLs** at each provider:
  - Meta: `https://ot1-pro.com/api/webhooks/meta` (FB), `.../meta-ig` (IG)
  - Telegram: `https://ot1-pro.com/api/webhooks/telegram`
  - Stripe: `https://ot1-pro.com/api/stripe/webhook`
  - TikTok / Snapchat / Slack / Discord as applicable
- [ ] **Verify end-to-end**: send a test message from a real Facebook page, confirm it lands in the inbox
- [ ] **Cut the laptop tunnel**: `Stop-Service cloudflared; Set-Service cloudflared -StartupType Disabled`

### Phase C2 — Backups (½ day, $0)

The current `scripts/backup-db.ps1` copies SQLite to OneDrive. We need real backups:

- [ ] **Add a nightly MySQL dump** to crontab (deploy user):
  ```
  0 2 * * * mysqldump -u deploy -pSTRONG_PASS one_inbox | gzip > /tmp/db-$(date +\%F).sql.gz && \
    aws s3 cp /tmp/db-$(date +\%F).sql.gz s3://one-inbox-backups/ --endpoint-url https://<account-id>.r2.cloudflarestorage.com && \
    rm /tmp/db-$(date +\%F).sql.gz
  ```
- [ ] **Keep last 7 days only** — add an S3 lifecycle rule on the `one-inbox-backups` R2 bucket (Cloudflare dashboard → R2 → bucket settings → Object lifecycle)
- [ ] **Verify backup restoration**: download the latest dump, restore to a local MySQL, run `php artisan migrate:status` to confirm it boots
- [ ] **Schedule a weekly restore drill** — add a recurring calendar reminder

### Phase C3 — Observability (½ day, $0–$20/mo)

Forensics is a 30-second Postgres check if we have the logs. Right now we have none on the laptop beyond `storage/logs/laravel.log`.

- [ ] **Forge's server-level metrics** (CPU, RAM, disk, MySQL, Redis) — free, in the Forge dashboard
- [ ] **Configure `LOG_STACK` to include Sentry** if we want exception tracking (free tier: 5K events/mo)
- [ ] **Set up Uptime monitoring**: free UptimeRobot or Healthchecks.io pinging `/up` every 60s, alerting via Telegram bot (we already have a Telegram bot for the app — reuse it)
- [ ] **Log tailing** without Forge dashboard: `journalctl -u oneinbox-queue -f` and `journalctl -u oneinbox-reverb -f` over SSH
- [ ] **Cloudflare Analytics** for traffic — already free, surface in the Cloudflare dashboard

### Phase C4 — Cutover & decommission laptop-as-prod (1 day)

The riskiest moment: DNS points at the Hostinger VPS, but `ot1-pro.com` may still resolve to the laptop tunnel briefly.

- [ ] **Set low TTL (300s) on `ot1-pro.com` A record 24h before cutover**
- [ ] **Lower TTL, confirm propagated** (`dig ot1-pro.com`)
- [ ] **Update A record to point at the Hostinger VPS's public IP**
- [ ] **Verify**: `curl -I https://ot1-pro.com` should return `Server: nginx` with a Cloudflare-signed header
- [ ] **Stop & disable the laptop's `cloudflared` service**: `Stop-Service cloudflared; Set-Service cloudflared -StartupType Disabled`
- [ ] **Re-verify all webhooks** after cutover — Meta in particular re-validates sometimes
- [ ] **Tell one customer to retest their full inbound flow** before considering it done
- [ ] **Delete the `cloudflared.exe` binary from the repo root** — it no longer belongs
- [ ] **Delete `one-inbox-prod/`** if it still exists (launch-plan.md open todo)
- [ ] **Delete `tunnel.conf`** from Herd nginx config (Herd can keep its `tunnel.conf` block in `~/.config/herd/bin/` but the repo reference is dead)
- [ ] **Set `APP_URL=https://ot1-pro.com` is the same on both dev and prod** — this is the canonical URL forever, both environments just hit it differently

---

## 4. Files to change in the repo

In order of importance:

### 4.1 `.env.example` — regenerate
Add every key from `config/services.php`, `config/reverb.php`, `config/broadcasting.php`, `config/cashier.php`, `config/session.php`, `config/logging.php`. Currently ends at line 145 with `WUZAPI_QR_ENABLED`. Should be 220+ lines. The single biggest source of "I forgot to add this key" deploy bugs.

### 4.2 `.github/workflows/deploy.yml` — collapse to Forge only
Server A job (self-hosted runner on laptop) becomes dev-only. the Hostinger VPS is owned by Forge's built-in GitHub deploy hook — remove the the Hostinger VPS block from the workflow entirely. The Server C skeleton (WhatsApp gateway) stays commented out for when Phase C6 revives it, but no live secrets or steps for it in this iteration.

### 4.3 `setup-services.ps1` — stop being the source of truth
This file is Windows-NSSM only. It should be deleted from cloud-deploy context, kept only as a dev-env setup helper. Add a comment at the top: `# Dev environment only. Production systemd units are managed by Laravel Forge on the Hostinger VPS.`

### 4.4 `deploy.ps1` — delete or repurpose
This is the Windows-only PowerShell deploy for the laptop. After C5, it's dead. Either delete it, or rename to `deploy-laptop.ps1` with a docstring saying it only applies while Server A is still the prod target.

### 4.5 `tasks/launch-plan.md` — link to this plan
Add a line at the top: "Cloud plan: see `tasks/cloud-deployment-plan.md` (supersedes Phase 2 of this file for post-laptop deployments)."

### 4.6 Add a `Dockerfile` (optional, do later)
For the Hostinger VPS we don't need a Docker image — Forge runs PHP directly. Don't add a Dockerfile unless we need to scale to multiple app servers. (When WhatsApp is revived in Phase C6, its Server C uses the existing compose files, not a bespoke Dockerfile.)

### 4.7 `app/Providers/AppServiceProvider.php` — force root URL
The deployment-architecture.md notes this as INFO-only. Worth doing now while we're changing the URL surface:

```php
public function boot(): void
{
    URL::forceRootUrl(config('app.url'));
    URL::forceScheme('https');
}
```

This is a defense-in-depth fix so even if nginx mis-sets the Host header, generated URLs use the canonical APP_URL.

---

## 5. The 10 risks I see, ranked

| # | Risk | Mitigation |
|---|---|---|
| 1 | **Meta App Review fails because webhook URL changes mid-review** | Pin the webhook URL at Meta console to `ot1-pro.com` and don't change it during the review window. Move Meta's URL last, after all other webhooks are verified. |
| 2 | **`APP_DEBUG=true` accidentally enabled in production `.env`** | Add a deploy-time guard: a `preflight` step in Forge's deploy script that fails the deploy if `grep -q 'APP_DEBUG=true' .env`. |
| 3 | **MySQL connection pool exhausted under webhook burst** | Forge's default MySQL has `max_connections=151`. On a 2 vCPU KVM 2, real ceiling is lower — monitor. Set `DB_PERSISTENT=true` in `.env` to reuse connections. Add `mysqladmin -h 127.0.0.1 status` to uptime checks. Upgrade path: KVM 4 (4 vCPU / 16 GB, ~$13/mo). |
| 4 | **Reverb WebSocket + Cloudflare proxying** | Cloudflare Free **does** support WSS through orange-cloud DNS (has since 2018 — no manual toggle needed). Add `reverb.ot1-pro.com` as a separate proxied A record pointing to the Hostinger VPS. Verify the connection with `wscat` before assuming it works end-to-end. |
| 4b | **Hostinger uptime less battle-tested than Hetzner/DO** | Set up UptimeRobot on `/up` every 60s → Telegram alert. If Hostinger has a bad month (>1 hour downtime), swap the underlying VPS via Forge to whatever provider is Apple Pay-compatible then (or by that point we may have unlocked other payment methods). Swap = ~1 hour of work — Forge re-provisions on a new IP, DNS flip, done. |
| 5 | **No CDN for static assets** | The current ASSET_URL serves from the same origin. Add Cloudflare Cache Rules: `*.css, *.js, *.svg, *.woff2` from `ot1-pro.com/build/*` → cache 1 year. Reduces PHP-FPM load for repeat visitors. |
| 6 | **`META_WEBHOOK_VERIFY_TOKEN` rotation requires touching Meta console** | Already a launch-plan todo. Do it on the Hostinger VPS's first deploy, not before — reduces the number of moving parts. |
| 7 | **Redis persistence is off by default in Forge's install** | Enable `appendonly yes` in `redis.conf` so queue jobs and sessions survive a Redis restart. Otherwise a Redis crash mid-deploy loses every in-flight queue job. |
| 8 | **The deploy script changes the working directory to `one-inbox-prod/` but the GitHub workflow runs from `one-inbox/`** | Pre-existing bug in `deploy.ps1:2` vs `.github/workflows/deploy.yml:17`. After C4, `deploy.ps1` is gone, so this is moot. Document in `lessons.md` for future reference. |
| 9 | **Meta App Review lag while WhatsApp is off** | With WhatsApp deferred, WhatsApp Cloud API permissions can't be reviewed in this cycle. Submit Meta App Review scoped to `pages_messaging` / `instagram_manage_messages` only — resubmit for `whatsapp_business_messaging` after Phase C6. |

---

## 6. Decision log — what I would ask you before starting

1. **Domain**: keep `ot1-pro.com` for the cloud prod, or buy a new one (e.g. `one-inbox.app`)? Keeping `ot1-pro.com` means zero marketing URL change; new domain means a clean break. **Recommendation: keep `ot1-pro.com`** because customers already have it, and the SSL cert history is clean.
2. **MySQL or Postgres on the Hostinger VPS?** Both are first-class. **Recommendation: MySQL 8** because that's what Forge installs and we have no Postgres-specific features.
3. **Do we move Wuzapi/Evolution off the laptop today?** ~~It currently runs in Docker on the laptop per `docker-compose.wuzapi.yml`.~~ **Deferred (2026-07-06):** WhatsApp is out of scope for this cutover. Wuzapi stays on the laptop (or off) until Phase C6.
4. **Are we okay with the ~$21/mo run rate?** Well below `launch-plan.md`'s $31 projection because (a) WhatsApp gateway deferred and (b) Hostinger + R2 is cheaper than Hetzner + Hetzner Object Storage. **Recommendation: yes.** Add ~$5/mo when Phase C6 lands (Hostinger KVM 1 for gateways). If we're under $100/mo revenue, this is still too much; if we're over $1k/mo, upgrade to KVM 4 first.
5. **Add a second VPS for HA?** Not in this plan. **Recommendation: defer.** One prod server + a tested backup procedure is the right move for the next 6 months. HA costs 2× infra and 5× ops complexity. Revisit when monthly revenue crosses $2k.
6. **Self-hosting plan from memory — does this plan invalidate it?** No. The selfhosted infra plan in memory is a *future* option for moving off Hostinger to two PCs in Egypt. Cloud plan and selfhosted plan converge at the same app architecture; only the *servers* are different. Both follow Phase 4's "Production Checklist" in `progress.md`.
7. **What's the Meta App Review timing?** If it's "next week," Phase C0 + C1 must compress to 3 days. That's tight but doable. If it's "next month," we have breathing room to do this properly.
8. **What if Apple Pay stops being our only payment method later?** Great — the plan barely changes. Forge is provider-agnostic, so swapping the underlying VPS from Hostinger to Hetzner is ~1 hour of work (provision, DNS flip, done). R2 stays either way because its zero-egress is worth keeping.

---

## 7. What this plan does NOT cover

- Disaster recovery across regions (intentional — single region is fine)
- Zero-downtime deploys (Forge's "Instant Deploy" feature is good enough; downtime is <5s)
- Penetration testing or compliance audits (out of scope at this stage; revisit pre-Enterprise)
- Mobile app deployment (Capacitor / App Store / Play Store — Phase 7 of launch-plan)
- Marketing site deployment (static site, can be Cloudflare Pages later)
- Self-hosting in Egypt (separate plan in `memory/selfhosted_infra_plan.md`)
- Multi-provider payment (Apple Pay is currently the only option; if other cards start working later, revisit provider choice)

---

## 8. What to do when something breaks

| Symptom | First check | Then |
|---|---|---|
| `ot1-pro.com` returns 502 | `curl http://187.77.67.94/up` from any box | If 200, it's Cloudflare. If 502, it's PHP-FPM → `systemctl restart php8.4-fpm` over SSH. |
| Hostinger VPS unreachable | Check hPanel status page | If Hostinger has an outage, wait it out. If chronic (>1 hour twice in a month), plan a Forge-driven migration to a different Apple-Pay-friendly VPS. |
| Webhooks returning 500 in Meta console | `forge logs` → filter on `/api/webhooks/meta` | Most likely `META_APP_VERIFIED` flipped, see `CLAUDE.md` pin #1 |
| Queue backed up (jobs > 1 minute old) | `journalctl -u oneinbox-queue -n 50` | `systemctl restart oneinbox-queue`; if jobs failing, see ARCHITECTURE §11 (`canDispatchAi`) |
| Reverb disconnecting | `journalctl -u oneinbox-reverb -n 50` | `systemctl restart oneinbox-reverb`; verify `REVERB_HOST=0.0.0.0` not `127.0.0.1` in `.env` |
| MySQL out of connections | `mysqladmin -u root status` | `set global max_connections = 500;` in `/etc/mysql/mysql.conf.d/mysqld.cnf`; or upgrade to KVM 4 |

---

## 9. Appendix — what changes for the user

After this plan ships, the *only* things that change for you day-to-day:

- **Deploys**: push to `main` on GitHub → Forge deploys to the Hostinger VPS automatically (1–2 minutes, instead of running `deploy.ps1` manually)
- **Local dev**: still `php artisan serve` or Herd, no change
- **Database**: dev still uses SQLite (faster iteration), prod uses MySQL
- **Adding a queue worker**: Forge UI, not a `setup-services.ps1` line
- **Webhooks**: configured per-environment in `.env`, but the URL pattern `https://ot1-pro.com/api/webhooks/*` is the same on both

The wins:
- The laptop can crash and stay down for a week — production is unaffected (assuming no active WhatsApp customer, since Wuzapi still runs there)
- A second person can take over prod ops using only the Forge dashboard
- Meta App Review can complete on a real production URL (scoped to FB/IG; WhatsApp permissions submitted later — see Risk #9)
- `deploy.ps1`, `tunnel.conf`, `cloudflared.exe` in the repo, `setup-services.ps1`'s prod entries, and the entire `one-inbox-prod/` directory are gone

The losses:
- A ~$9/mo bill (vs $0) — Hostinger only, paid
- **Two** new accounts: Hostinger (done ✅), Cloudflare R2 (inside existing Cloudflare account)
- No deploy dashboard — restarts/env edits require SSH. Acceptable trade-off for $0/mo saved on Forge
- We can't hot-patch prod by editing a file on the laptop anymore — must go through git (this is a feature, not a bug)
