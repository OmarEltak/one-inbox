# Self-Hosting Guide — One Inbox

Primary: Cloudflare Tunnel
Backup: Tailscale Funnel

---

## PART 1 — Buy Domain from Cloudflare

1. Go to https://www.cloudflare.com/products/registrar/
2. Sign in (or create free account)
3. Click **Register a Domain**
4. Search for your domain name → buy it (~$8-12/yr, at-cost pricing, no markup)
5. Cloudflare automatically manages the DNS — nothing else to configure here

---

## PART 2 — Cloudflare Tunnel (Primary Setup)

### Prerequisites
- Domain bought and on Cloudflare ✓
- `cloudflared.exe` downloaded (already at `C:\Users\NanoChip\Downloads\cloudflared.exe`)
- Laravel Herd running the app at `https://one-inbox.test`

### Step 1 — Move cloudflared to a permanent location
```powershell
mkdir C:\tools
copy C:\Users\NanoChip\Downloads\cloudflared.exe C:\tools\cloudflared.exe
```

### Step 2 — Authenticate with Cloudflare
```powershell
C:\tools\cloudflared.exe tunnel login
```
Browser opens → log in to your Cloudflare account → select your domain → authorize.
A credentials file is saved to `C:\Users\NanoChip\.cloudflared\`

### Step 3 — Create the tunnel
```powershell
C:\tools\cloudflared.exe tunnel create one-inbox
```
Copy the tunnel ID printed (looks like: `abc123-def456-...`) — you need it in the config.

### Step 4 — Create DNS routes
```powershell
# Main app
C:\tools\cloudflared.exe tunnel route dns one-inbox yourdomain.com
C:\tools\cloudflared.exe tunnel route dns one-inbox www.yourdomain.com

# WhatsApp / Evolution API (if needed)
C:\tools\cloudflared.exe tunnel route dns one-inbox wa.yourdomain.com
```

### Step 5 — Create config file
Create file: `C:\Users\NanoChip\.cloudflared\config.yml`
```yaml
tunnel: one-inbox
credentials-file: C:\Users\NanoChip\.cloudflared\<YOUR-TUNNEL-ID>.json

ingress:
  - hostname: yourdomain.com
    service: https://one-inbox.test
    originRequest:
      noTLSVerify: true
      originServerName: one-inbox.test
  - hostname: www.yourdomain.com
    service: https://one-inbox.test
    originRequest:
      noTLSVerify: true
      originServerName: one-inbox.test
  - hostname: wa.yourdomain.com
    service: http://localhost:8088
  - service: http_status:404
```
Replace `<YOUR-TUNNEL-ID>` with the ID from Step 3.

### Step 6 — Test it
```powershell
C:\tools\cloudflared.exe tunnel run one-inbox
```
Visit `https://yourdomain.com` — should load the app. If yes, Ctrl+C and continue.

### Step 7 — Install as Windows service (auto-starts on boot)
```powershell
# Open PowerShell as Administrator
C:\tools\cloudflared.exe service install
Start-Service cloudflared
```

Verify it's running:
```powershell
Get-Service cloudflared
```

### Step 8 — Update .env
```env
APP_URL=https://yourdomain.com
APP_ENV=production
APP_DEBUG=false
```

Then clear cache:
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Step 9 — Update Meta webhook
In Meta Developer Console:
```
https://yourdomain.com/api/webhooks/meta
```

### Step 10 — Run Queue Worker + Scheduler as Windows Services

Download NSSM: https://nssm.cc/download
Extract `nssm.exe` to `C:\tools\nssm.exe`

Find your PHP path first:
```powershell
Get-Command php | Select-Object -ExpandProperty Source
```

Then install services (replace PHP path if different):
```powershell
# Open PowerShell as Administrator

# Queue Worker
C:\tools\nssm.exe install OneInboxWorker "C:\Users\NanoChip\AppData\Local\Herd\bin\php.exe"
C:\tools\nssm.exe set OneInboxWorker AppParameters "C:\Users\NanoChip\Herd\one-inbox\artisan queue:work redis --sleep=3 --tries=3 --timeout=60"
C:\tools\nssm.exe set OneInboxWorker AppDirectory "C:\Users\NanoChip\Herd\one-inbox"
C:\tools\nssm.exe set OneInboxWorker Start SERVICE_AUTO_START
Start-Service OneInboxWorker

# Scheduler
C:\tools\nssm.exe install OneInboxScheduler "C:\Users\NanoChip\AppData\Local\Herd\bin\php.exe"
C:\tools\nssm.exe set OneInboxScheduler AppParameters "C:\Users\NanoChip\Herd\one-inbox\artisan schedule:work"
C:\tools\nssm.exe set OneInboxScheduler AppDirectory "C:\Users\NanoChip\Herd\one-inbox"
C:\tools\nssm.exe set OneInboxScheduler Start SERVICE_AUTO_START
Start-Service OneInboxScheduler
```

Verify:
```powershell
Get-Service OneInboxWorker, OneInboxScheduler
```

---

## PART 3 — Tailscale Funnel (Backup Plan)

Use this if Cloudflare Tunnel has issues. Does NOT protect from bots — use temporarily only.

### Step 1 — Install Tailscale
Download from: https://tailscale.com/download/windows
Install and sign in with Google/GitHub/email (free account).

### Step 2 — Enable Funnel
```powershell
# Open PowerShell as Administrator
tailscale funnel --bg 443
```
`--bg` runs it in the background.

Tailscale gives you a public URL like:
```
https://your-machine-name.tail12345.ts.net
```

### Step 3 — Point your domain to Tailscale temporarily
In Cloudflare DNS:
1. Go to your domain → DNS → Records
2. Find the `A` record for `yourdomain.com`
3. Change it to a `CNAME` pointing to `your-machine-name.tail12345.ts.net`
4. Set **Proxy status to DNS only** (grey cloud) — Tailscale handles SSL itself

### Step 4 — Update .env temporarily
```env
APP_URL=https://yourdomain.com
```
(Same as before — no change needed if domain is the same)

### Step 5 — When Cloudflare is back
Stop Tailscale Funnel:
```powershell
tailscale funnel off
```
Revert DNS back to Cloudflare Tunnel (orange cloud proxy).

---

## PART 4 — DDNS for SSH access (so you can SSH into this PC from anywhere)

Add a DNS record that updates every 5 minutes with your current IP.

Create a Cloudflare API token:
1. Cloudflare Dashboard → Profile → API Tokens → Create Token
2. Use "Edit zone DNS" template → select your domain → create

Get your Zone ID and Record ID:
```powershell
# Get Zone ID (find it on your domain's Overview page in Cloudflare dashboard)
# Create an A record manually first: server-a.yourdomain.com → any IP

# Then run this to get the record ID:
curl "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID/dns_records?name=server-a.yourdomain.com" `
  -H "Authorization: Bearer YOUR_API_TOKEN"
```

Create `C:\tools\ddns-update.ps1`:
```powershell
$ip = (Invoke-RestMethod "https://ifconfig.me/ip").Trim()
$headers = @{ "Authorization" = "Bearer YOUR_API_TOKEN"; "Content-Type" = "application/json" }
$body = @{ type="A"; name="server-a.yourdomain.com"; content=$ip; ttl=60 } | ConvertTo-Json
Invoke-RestMethod -Method Patch `
  -Uri "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID/dns_records/YOUR_RECORD_ID" `
  -Headers $headers -Body $body
```

Schedule it every 5 minutes (Task Scheduler):
```powershell
# Open PowerShell as Administrator
$action = New-ScheduledTaskAction -Execute "powershell.exe" -Argument "-File C:\tools\ddns-update.ps1"
$trigger = New-ScheduledTaskTrigger -RepetitionInterval (New-TimeSpan -Minutes 5) -Once -At (Get-Date)
Register-ScheduledTask -TaskName "DDNS Update" -Action $action -Trigger $trigger -RunLevel Highest
```

---

## Quick Reference — Services to manage

| Service | Command | Purpose |
|---------|---------|---------|
| Cloudflare Tunnel | `Start-Service cloudflared` | Public access |
| Queue Worker | `Start-Service OneInboxWorker` | Background jobs |
| Scheduler | `Start-Service OneInboxScheduler` | Email polling, reminders |
| Laravel Herd | Herd tray icon → Start | PHP + Nginx |

Check all at once:
```powershell
Get-Service cloudflared, OneInboxWorker, OneInboxScheduler
```

Restart all:
```powershell
Restart-Service cloudflared, OneInboxWorker, OneInboxScheduler
```
