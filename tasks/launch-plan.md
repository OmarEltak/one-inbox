# One Inbox — Launch Plan

Last updated: 2026-03-23

---

## Phase 0: Laptop Server Hardening (TEMPORARY — move to Forge ASAP)

> Server A is your laptop. It IS the dev machine and the prod server at the same time.
> Phase 0 makes it survive reboots. Phase 2 (Forge) eliminates all of this permanently.

### Critical — every reboot kills production until these are done
- [x] **NSSM — Queue, Scheduler, Reverb as Windows services** → auto-start on boot ✅
- [ ] **`cloudflared service install`** — installs Cloudflare Tunnel as a Windows service
      → Without this, the tunnel dies on every reboot and takes down `ot1-pro.com`
      → Run once in PowerShell as Administrator: `cloudflared service install`
      → Verify: `Get-Service cloudflared` should show Running after next reboot

### Cleanup
- [ ] Delete `one-inbox-prod/` directory — it's a stale copy, `one-inbox/` is the real app
      → First update `tunnel.conf` HTTP_HOST from `one-inbox-prod.test` → `one-inbox.test`
      → Then reload Herd nginx, verify `ot1-pro.com` works, then delete the directory
- [ ] Set `APP_DEBUG=false` in `.env` → run `php artisan config:cache`

### Security
- [ ] Set up daily SQLite backup PowerShell script → schedule via Windows Task Scheduler
- [ ] Enable Cloudflare WAF: dashboard → Security → WAF → Managed Rules → turn on
- [ ] Rotate `META_WEBHOOK_VERIFY_TOKEN` to a random value → update in Meta console

---

## Phase 1: GitHub Private Repository
- [ ] Verify .gitignore covers .env, vendor, node_modules
- [ ] Push current code to main branch on GitHub (initial commit already done — push any unpushed changes)
- [ ] Confirm repo is private

---

## Phase 1.5: Auto-Deploy for Server B and C

> Note: Server A (laptop) is updated manually — it's your dev machine, you control when code goes live on it.
> Server B and C get auto-deployed from GitHub on every push to main.

- [ ] Add GitHub Actions workflow: `.github/workflows/deploy.yml`
      On push to `main` → SSH into Server B → git pull + composer install + npm build + artisan cache
      Same for Server C in parallel
- [ ] Set up SSH key: generate a deploy key on your laptop, add public key to Server B/C `authorized_keys`,
      add private key as GitHub Actions secret (`DEPLOY_KEY_B`, `DEPLOY_KEY_C`)
- [ ] Add GitHub secrets: `SERVER_B_HOST`, `SERVER_B_USER`, `SERVER_C_HOST`, `SERVER_C_USER`
- [ ] Test: push a small change → confirm both servers update automatically

---

## Phase 2: Deploy Online

### Stack
| Service | Purpose | Cost/mo |
|---|---|---|
| Hetzner CX32 (4 vCPU, 8GB) | Main app server | ~$11 |
| Hetzner CX22 (2 vCPU, 4GB) | Evolution API (WhatsApp QR) | ~$6 |
| Laravel Forge | Server mgmt, SSL, deployments, queue workers | $12 |
| Cloudflare (free) | DNS, DDoS, proxy | $0 |
| Hetzner Object Storage | File/media uploads | ~$2 |
| Domain (.com) | e.g. one-inbox.io | ~$1 |
| **Total** | | **~$32/mo** |

### Management
- **Laravel Forge** = main control panel (99% of operations)
- **Hetzner** = billing only (linked to Forge once)
- **Cloudflare** = DNS only (set once)
- **GitHub** = code (Forge watches and auto-deploys on push)

### Steps
- [ ] Register domain
- [ ] Create Cloudflare account → add domain → get nameservers
- [ ] Create Hetzner Cloud account
- [ ] Create Laravel Forge account → connect Hetzner + GitHub
- [ ] Provision CX32 server via Forge (PHP 8.2, MySQL, Redis, Node)
- [ ] Create site in Forge → link to GitHub repo → configure .env
- [ ] Run `php artisan migrate --force` + `npm run build`
- [ ] Enable queue worker in Forge (ProcessIncomingMessage, SendAiResponse, etc.)
- [ ] Enable Reverb WebSocket server in Forge
- [ ] Provision CX22 server → deploy Evolution API via Docker
- [ ] Update Meta webhook URL → `https://yourdomain.com/api/webhooks/meta`
- [ ] Update Telegram webhook URL
- [ ] Update Evolution webhook URL
- [ ] Test all webhook endpoints

---

## Phase 3: Meta App Review

### Permissions to Request
- `pages_messaging` — Facebook Messenger
- `instagram_manage_messages` — Instagram DMs
- `whatsapp_business_messaging` — WhatsApp Cloud API
- `pages_manage_metadata` — Webhook subscriptions

### Checklist
- [ ] App running on production HTTPS URL
- [ ] Privacy Policy at /privacy ✅
- [ ] Terms of Service at /terms ✅
- [ ] App icon 1024×1024
- [ ] Screenshots of working features
- [ ] Screen recording demo for each permission
- [ ] Business verification (for WhatsApp API)

---

## Phase 4: Test Normal User Process

End-to-end journey:
- [ ] Landing page → Register
- [ ] Connect Facebook page (OAuth)
- [ ] Connect Instagram
- [ ] Connect WhatsApp Business
- [ ] Connect Telegram bot
- [ ] Send test message from external account → appears in inbox
- [ ] AI auto-responds
- [ ] Agent manually replies
- [ ] Lead scoring updates
- [ ] Analytics reflect activity

---

## Phase 5: Pricing Page Update

### Tiers

| Plan | Price | Pages | AI Responses | Target |
|---|---|---|---|---|
| Free | $0/mo | 1 | 50/mo | Hook, not sustain |
| Starter | $29/mo | 3 | 500/mo | Small businesses |
| Pro | $79/mo | 5 | 2,000/mo | Growing teams |
| Enterprise | Custom | Unlimited | Unlimited | Agencies / large teams |

### Sales CTAs (no payment integration yet)
- Free → direct signup
- Starter / Pro → "Start Free Trial" → WhatsApp close (+201026361218)
- Enterprise → "Contact Sales" → WhatsApp link (+201026361218)

### Infrastructure Cost Per User
- Fixed infra: ~$32/mo
- AI (3x Gemini Max): ~$60/mo
- Total fixed: ~$92/mo

| # Teams | Cost/Team |
|---|---|
| 10 | $9.20 |
| 25 | $3.68 |
| 50 | $1.84 |
| 100 | $0.92 |

Variable: WhatsApp business-initiated conversations ~$0.08 each (first 1000 free/mo per WABA)

---

## Phase 6: New Platforms

### Tier 1 (build first)
- TikTok DMs — fastest growing, e-commerce
- LinkedIn Messages — B2B, high-value leads
- Google Business Messages — local businesses via Maps/Search

### Tier 2
- Twitter/X DMs
- Snapchat
- Viber (dominant in Middle East)

### Tier 3 (regional)
- WeChat, Line, Discord

---

## Phase 7: New UI

### Marketing site
- Modern SaaS landing page (animations, social proof, demo video, competitor comparison)

### App UI
- Mobile-responsive inbox
- Onboarding wizard for new users
- Dark mode
- Flux UI component polish

### Mobile app (future)
- Capacitor wrapper or React Native
- Push notifications
- App Store + Google Play
