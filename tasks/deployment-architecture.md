# One Inbox — Deployment Architecture & Security Review

> Written: 2026-03-22
> Status: **Laptop (Server A) is live** at `https://ot1-pro.com`. Servers B and C not yet provisioned.

---

## 1. What We Built

### The Problem We Solved

Laravel Herd serves `one-inbox.test` on your local machine. Meta webhooks, WhatsApp, and users all need a public HTTPS URL. The goal was to expose the local app to the internet permanently without ngrok (which gives a new URL every restart).

### The Solution: Cloudflare Tunnel

```
User Browser
     │ HTTPS (TLS terminated at Cloudflare edge)
     ▼
Cloudflare Edge (ot1-pro.com)
     │ HTTP via persistent encrypted tunnel (no open inbound ports)
     ▼
cloudflared daemon (Windows service on your laptop)
     │ HTTP to 127.0.0.1:8088
     ▼
nginx (tunnel.conf vhost — port 8088)
     │ fastcgi + HTTP_HOST override → one-inbox.test
     ▼
PHP-CGI (Herd, port 9084)
     │
     ▼
Laravel App
```

**Key insight**: Cloudflare Tunnel works by making an outbound connection from your machine to Cloudflare's edge. No inbound ports are opened on your router. This is why it works behind WE fiber NAT with no port forwarding.

---

## 2. Every Decision Made

### Decision 1: Port 8088 as the tunnel target (not `one-inbox.test` directly)

**What we tried first**: Route the tunnel directly to `http://one-inbox.test`
**Problem**: Herd's nginx only responds to `Host: one-inbox.test`. When cloudflared connected, it sent `Host: ot1-pro.com` (the public domain), which Herd didn't recognize → "Site not found."
**What we did instead**: Used the pre-existing `tunnel.conf` nginx vhost on port 8088, which overrides `HTTP_HOST` to `one-inbox.test` and sets `HTTPS=on` before passing to PHP. This tricks Laravel into thinking the request came from its own domain over HTTPS.

### Decision 2: ASSET_URL=https://ot1-pro.com

**Problem**: Laravel's `asset()` helper generates URLs using the request `Host` header, which is `one-inbox.test` (set by tunnel.conf). So assets were loading from `https://one-inbox.test/build/...`. Chrome's Private Network Access policy blocks HTTPS public pages from loading resources from `.test` domains (treated as private network).
**Fix**: `ASSET_URL` in `.env` explicitly overrides the base for all `asset()` calls, regardless of the request host.

### Decision 3: VITE_REVERB_HOST=ot1-pro.com + WebSocket proxy in tunnel.conf

**Problem**: The built JS was connecting WebSocket to `ws://localhost:8080`. A public HTTPS page can't connect to a localhost WebSocket — Chrome blocks it as a Private Network Access violation.
**Fix**:
- Changed `VITE_REVERB_HOST=ot1-pro.com`, `VITE_REVERB_PORT=443`, `VITE_REVERB_SCHEME=https` so the built JS connects to `wss://ot1-pro.com/app/...`
- Added a WebSocket proxy in tunnel.conf: requests to `/app/*` on port 8088 are proxied to Reverb on port 8080
- Ran `npm run build` to bake the new values into the JS bundle

### Decision 4: noTLSVerify: true in cloudflared config

**Why**: The tunnel routes to `http://127.0.0.1:8088` (plain HTTP). When `noTLSVerify: true` was set (from an earlier attempt with HTTPS origin), it was kept because it's harmless for HTTP origins.
**Note**: This setting only affects origin TLS verification — the Cloudflare ↔ user connection is always TLS. Can be removed since origin is plain HTTP.

### Decision 5: Cloudflare tunnel installed as Windows Service

**Why**: So it starts automatically on boot without needing to open a terminal. Installed via `cloudflared service install` with a registry patch to include `--config` pointing to `~/.cloudflared/config.yml`.

### Decision 6: SQLite database (current)

**Why**: Zero setup, works out of the box with Herd. Fine for early stage / single server.
**Limitation**: Can't be shared across multiple servers. Must migrate to MySQL before adding Server B.

---

## 3. Current Running Services (What Must Stay Up)

| Service | How It Runs | Terminal / Auto? |
|---------|-------------|-----------------|
| Laravel Herd (nginx + PHP) | Herd system tray app | Auto on login |
| cloudflared tunnel | Windows Service | Auto on boot |
| `php artisan reverb:start` | Manual terminal | **Manual** — needs automation |
| `php artisan queue:work` | Manual terminal | **Manual** — needs automation |
| `php artisan schedule:work` | Manual terminal | **Manual** — needs automation (email polling) |

### Startup Commands (run in 3 terminals)
```bash
# Terminal 1 — WebSocket server
php artisan reverb:start

# Terminal 2 — Background job worker
php artisan queue:work

# Terminal 3 — Scheduler (email polling every 2 min)
php artisan schedule:work
```

---

## 4. Security Analysis

### SECURE ✅

| Item | Why Secure |
|------|-----------|
| **No open inbound ports** | Cloudflare Tunnel is outbound-only. No port forwarding on your router. Attackers can't reach your machine directly. |
| **TLS to end users** | Cloudflare terminates HTTPS. Users always get a valid certificate. You never handle TLS on your machine. |
| **Cloudflare DDoS protection** | All traffic passes through Cloudflare's edge — built-in DDoS mitigation, rate limiting (configurable in Cloudflare dashboard). |
| **Webhook verify tokens** | `META_WEBHOOK_VERIFY_TOKEN=one_inbox_verify_2024` is validated on all incoming Meta webhooks. |
| **Session stored in DB** | `SESSION_DRIVER=database` — sessions are server-side, not in cookies. |
| **CSRF protection** | Laravel's CSRF middleware is enabled by default on all web routes. |
| **Passwords hashed** | `BCRYPT_ROUNDS=12` — strong bcrypt hashing. |
| **Assets from ot1-pro.com** | No Private Network Access leakage — all assets and WebSocket go through Cloudflare. |

### NOT SECURE / NEEDS FIXING ⚠️

| Item | Risk | Fix |
|------|------|-----|
| **`APP_DEBUG=true`** | **HIGH** — Stack traces exposed to users on errors. Leaks file paths, env values, SQL queries. | Set `APP_DEBUG=false` in production `.env`. |
| **`noTLSVerify: true` in cloudflared config** | LOW — Harmless since origin is HTTP, but should be cleaned up. | Remove the line — it's not needed for HTTP origins. |
| **Manual process management** | MEDIUM — If you close the terminal, `queue:work`, `reverb:start`, and `schedule:work` die. Queued jobs fail silently, WebSocket disconnects, emails stop fetching. | Install these as Windows Services via NSSM (see Section 6). |
| **SQLite on a laptop** | MEDIUM — SQLite is a file. No replication. No multi-server. Laptop drive fails = data gone. | Migrate to MySQL before adding Server B. Back up the SQLite file daily (see Section 5). |
| **No Cloudflare WAF rules** | MEDIUM — Default Cloudflare free tier has basic DDoS but no application-layer firewall rules. | Enable Cloudflare's free WAF preset in the dashboard (Security → WAF → Managed Rules). |
| **Secrets in `.env` file** | LOW for now — `.env` is gitignored and local. | When adding servers, use SSH to push `.env` rather than checking it into git. Never commit it. |
| **`META_WEBHOOK_VERIFY_TOKEN` is guessable** | LOW — "one_inbox_verify_2024" is predictable. | Change to a random string: `openssl rand -hex 32`. Update in Meta developer console. |
| **No HTTPS between cloudflared and nginx** | INFO — Traffic is plain HTTP on `127.0.0.1`. Acceptable on localhost (loopback only). | Non-issue as long as you don't route this port externally. |
| **Evolution API key in .env** | LOW now — But the Evolution API at port 8080 needs to be firewalled. | Ensure Evolution API only listens on 127.0.0.1, never 0.0.0.0. |
| **Canonical URL = one-inbox.test** | INFO — SEO only, no security risk. | Add `URL::forceRootUrl(config('app.url'))` in `AppServiceProvider::boot()`. |

### Quick Wins (do these now)
```bash
# 1. Disable debug mode
# In .env:
APP_DEBUG=false

# 2. Rotate Meta webhook verify token
php artisan tinker --execute="echo bin2hex(random_bytes(16));"
# Paste result into .env META_WEBHOOK_VERIFY_TOKEN and update in Meta console

# 3. Enable Cloudflare WAF
# Dashboard → Security → WAF → Managed Rules → Enable "Cloudflare Managed Ruleset"
```

---

## 5. Backup Plan

### Current State: No Backups
The SQLite database is a single file at `C:\Users\NanoChip\Herd\one-inbox\database\database.sqlite`. If the laptop dies, all data is lost.

### Immediate Backup (do this now)
Add a Windows Task Scheduler job that copies the SQLite file daily to a cloud location:

```powershell
# backup-db.ps1 — run this daily via Task Scheduler
$src = "C:\Users\NanoChip\Herd\one-inbox\database\database.sqlite"
$dst = "C:\Users\NanoChip\OneDrive\Backups\one-inbox\db-$(Get-Date -Format 'yyyy-MM-dd').sqlite"
Copy-Item $src $dst
# Keep last 30 days only
Get-ChildItem "C:\Users\NanoChip\OneDrive\Backups\one-inbox\" |
  Sort-Object LastWriteTime |
  Select-Object -SkipLast 30 |
  Remove-Item
```

### Failover: What Happens if the Laptop Dies

| Scenario | Impact | Recovery |
|----------|--------|----------|
| Laptop restarts | cloudflared auto-restarts (service). Herd auto-starts on login. Reverb/queue/scheduler need manual restart. | Run 3 startup commands. ETA: 2 min. |
| Laptop shuts off mid-day | All traffic fails. cloudflared tunnel goes dead. | Power on → auto-recover in ~1 min. Manual services need restart. |
| Laptop hard drive fails | **Total data loss** if no backup. | Restore from OneDrive backup. App code from git. |
| ISP outage | All traffic fails. Cloudflare shows "tunnel unavailable." | Nothing to do until ISP recovers. |

### Backup Plan with 2+ Servers (Section 7)
With Server B added, Cloudflare Tunnel automatically stops routing to a dead server within ~30 seconds. Server B continues serving traffic. No manual failover needed — Cloudflare health-checks both tunnel connections.

---

## 6. Automate Startup Services (NSSM) — Do This Now

Install Reverb, Queue Worker, and Scheduler as Windows Services so they survive terminal closes and reboots.

### Step 1: Install NSSM
```powershell
# Via Chocolatey
choco install nssm

# Or download from https://nssm.cc/download and put in C:\tools\nssm\
```

### Step 2: Install Services
Open PowerShell as Administrator:

```powershell
$phpPath = "C:\Program Files\Herd\resources\app.asar.unpacked\resources\bin\php\php.exe"
$appPath = "C:\Users\NanoChip\Herd\one-inbox"
$artisan = "$appPath\artisan"

# Laravel Reverb (WebSocket server)
nssm install OneInboxReverb $phpPath "$artisan reverb:start"
nssm set OneInboxReverb AppDirectory $appPath
nssm set OneInboxReverb AppStdout "$appPath\storage\logs\reverb.log"
nssm set OneInboxReverb AppStderr "$appPath\storage\logs\reverb-error.log"
nssm set OneInboxReverb Start SERVICE_AUTO_START
nssm start OneInboxReverb

# Queue Worker
nssm install OneInboxQueue $phpPath "$artisan queue:work --sleep=3 --tries=3 --max-time=3600"
nssm set OneInboxQueue AppDirectory $appPath
nssm set OneInboxQueue AppStdout "$appPath\storage\logs\queue.log"
nssm set OneInboxQueue AppStderr "$appPath\storage\logs\queue-error.log"
nssm set OneInboxQueue Start SERVICE_AUTO_START
nssm start OneInboxQueue

# Scheduler
nssm install OneInboxScheduler $phpPath "$artisan schedule:work"
nssm set OneInboxScheduler AppDirectory $appPath
nssm set OneInboxScheduler AppStdout "$appPath\storage\logs\scheduler.log"
nssm set OneInboxScheduler AppStderr "$appPath\storage\logs\scheduler-error.log"
nssm set OneInboxScheduler Start SERVICE_AUTO_START
nssm start OneInboxScheduler
```

### Manage Services
```powershell
nssm status OneInboxReverb
nssm restart OneInboxReverb
nssm stop OneInboxQueue
```

---

## 7. Setting Up Server B and C — Complete Guide (A to Z)

> All servers run **Windows**. Every step below is written for someone who has never done this before.
> Read the whole section once before starting. Then follow each step in order.
> **Do not skip steps.** Each one builds on the previous.

---

### Overview of What You're Building

```
Your Laptop (Server A)                New PC (Server B or C)
──────────────────────                ──────────────────────
MySQL ◄──────────────────────────────── connects here
Redis ◄──────────────────────────────── connects here
Laravel app (live)                    Laravel app (live)
Cloudflare tunnel                     Cloudflare tunnel
                    ↓ both tunnels ↓
              Cloudflare load-balances traffic
              between Server A and Server B automatically
```

Server B is a copy of Server A that shares the same database and cache.
If Server A goes down, Cloudflare automatically sends all traffic to Server B within 30 seconds.

---

### PART 1 — Do This on Server A (Your Laptop) First

> You only do Part 1 once, before you set up any extra server.

---

#### A1 — Enable MySQL in Herd

1. Find the Herd icon in your **system tray** (bottom-right corner, near the clock)
2. Click it → click **Services**
3. Find **MySQL** and toggle it **ON**
4. Wait 5 seconds

**How to verify:** Open PowerShell and run:
```powershell
Test-NetConnection -ComputerName 127.0.0.1 -Port 3306
```
You should see `TcpTestSucceeded : True`

---

#### A2 — Create the Database

1. Open PowerShell and run this to connect to MySQL:
```powershell
& "C:\Program Files\Herd\resources\app.asar.unpacked\resources\bin\mysql\bin\mysql.exe" -u root
```
You should see a `mysql>` prompt. That means you're inside MySQL.

2. Copy and paste these lines one by one. Replace `PICK_A_STRONG_PASSWORD` with a real password and **write it down** — you'll need it later:
```sql
CREATE DATABASE one_inbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'one_inbox'@'localhost' IDENTIFIED BY 'PICK_A_STRONG_PASSWORD';
CREATE USER 'one_inbox'@'%' IDENTIFIED BY 'PICK_A_STRONG_PASSWORD';
GRANT ALL ON one_inbox.* TO 'one_inbox'@'localhost';
GRANT ALL ON one_inbox.* TO 'one_inbox'@'%';
FLUSH PRIVILEGES;
EXIT;
```

**How to verify:** Run this — you should see `one_inbox` in the list:
```powershell
& "C:\Program Files\Herd\resources\app.asar.unpacked\resources\bin\mysql\bin\mysql.exe" -u root -e "SHOW DATABASES;"
```

---

#### A3 — Enable Redis in Herd

1. Click Herd tray icon → **Services**
2. Find **Redis** and toggle it **ON**

**How to verify:**
```powershell
Test-NetConnection -ComputerName 127.0.0.1 -Port 6379
```
You should see `TcpTestSucceeded : True`

---

#### A4 — Open Firewall for Server B/C

Server B needs to reach MySQL (port 3306) and Redis (port 6379) on your laptop over the local network.

Open PowerShell **as Administrator** (right-click PowerShell → Run as administrator) and run:
```powershell
New-NetFirewallRule -DisplayName "MySQL for OT1 Pro" -Direction Inbound -Protocol TCP -LocalPort 3306 -Action Allow
New-NetFirewallRule -DisplayName "Redis for OT1 Pro" -Direction Inbound -Protocol TCP -LocalPort 6379 -Action Allow
```

---

#### A5 — Find Your Laptop's Local IP

```powershell
ipconfig
```
Look for **IPv4 Address** under your active connection (Wi-Fi or Ethernet). It will look like `192.168.1.XXX`.
**Write this down.** Server B will need it.

---

#### A6 — Switch the App from SQLite to MySQL

1. Open `C:\Users\NanoChip\Herd\one-inbox\.env` in any text editor
2. Find the database section and replace it with:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=one_inbox
DB_USERNAME=one_inbox
DB_PASSWORD=PICK_A_STRONG_PASSWORD

REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_CLIENT=phpredis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```
3. Open PowerShell in the project folder and run:
```powershell
php artisan migrate --force
php artisan config:cache
```

**How to verify:** Open `https://ot1-pro.com` — if it loads normally, the switch worked. If it errors, check the password matches what you set in A2.

---

### PART 2 — Do This on the New PC (Server B)

> Do all of these steps on the NEW Windows PC, not your laptop.
> Repeat this entire Part 2 again for Server C when you're ready.

---

#### B1 — Install Laravel Herd

1. On the new PC, open a browser and go to `https://herd.laravel.com`
2. Click **Download for Windows** and run the installer
3. Follow the installer — default settings are fine
4. After install, Herd will appear in the **system tray** (bottom-right near the clock)
5. Click the Herd icon to make sure it opened correctly

**How to verify:** Open PowerShell and run `php --version` — you should see PHP 8.x

---

#### B2 — Install Git

1. Go to `https://git-scm.com/download/win`
2. Download and run the installer
3. On the screen that asks about PATH, choose: **"Git from the command line and also from 3rd-party software"**
4. Everything else → keep defaults → click Next until it finishes

**How to verify:** Open a new PowerShell window and run `git --version` — you should see a version number

---

#### B3 — Install Node.js

1. Go to `https://nodejs.org`
2. Click the **LTS** version (the one on the left)
3. Run the installer → keep all defaults

**How to verify:** Open a new PowerShell window and run `node --version` — you should see a version number

---

#### B4 — Install Composer

1. Go to `https://getcomposer.org/Composer-Setup.exe`
2. Download and run it
3. It will ask you to find PHP — point it to Herd's PHP:
   `C:\Program Files\Herd\resources\app.asar.unpacked\resources\bin\php\php.exe`
4. Finish the installer

**How to verify:** Open a new PowerShell window and run `composer --version`

---

#### B5 — Clone the App from GitHub

1. Open PowerShell and run:
```powershell
cd C:\Users\$env:USERNAME\Herd
git clone https://github.com/YOUR_GITHUB_USERNAME/one-inbox.git one-inbox
cd one-inbox
```
Replace `YOUR_GITHUB_USERNAME` with the actual GitHub username/org.

If the repo is private, you'll be asked to log in. Use your GitHub username and a **Personal Access Token** (not your password). Create one at: GitHub → Settings → Developer Settings → Personal Access Tokens → Classic → New Token → check `repo` → generate.

**How to verify:** Run `dir` — you should see folders like `app`, `resources`, `routes`, etc.

---

#### B6 — Install PHP Packages

In PowerShell, still inside the project folder:
```powershell
composer install --no-dev --optimize-autoloader
```
This will take 1–2 minutes.

**How to verify:** Run `dir vendor` — you should see a `laravel` folder inside

---

#### B7 — Copy the .env File from Server A

The `.env` file holds all the secrets and config. It's not in GitHub (intentionally).

**Option A — USB stick:**
- On Server A: copy `C:\Users\NanoChip\Herd\one-inbox\.env` to a USB stick
- On Server B: paste it into `C:\Users\USERNAME\Herd\one-inbox\.env`

**Option B — Windows file sharing:**
- On Server A: right-click the `.env` file → Share → share with the other PC
- On Server B: open File Explorer → Network → find Server A → copy the file

Once the file is on Server B, open it and change **only these lines**:
```env
DB_HOST=192.168.1.XXX        ← Server A's local IP (from step A5)
REDIS_HOST=192.168.1.XXX     ← same IP as above
APP_DEBUG=false
```
Everything else stays exactly the same as Server A.

---

#### B8 — Build the Frontend

```powershell
npm install
npm run build
```
This takes 1–3 minutes.

**How to verify:** Run `dir public\build` — you should see a folder called `assets`

---

#### B9 — Cache the Config

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> ⚠️ Do NOT run `php artisan migrate` on Server B. Only Server A runs migrations.

---

#### B10 — Set Up Herd to Serve the App

1. Click the **Herd tray icon**
2. Click **Sites**
3. The `one-inbox` folder should already be listed because it's inside the Herd directory
4. If it's not there: click **Add Site** → browse to `C:\Users\USERNAME\Herd\one-inbox`

**How to verify:** Open a browser on Server B and go to `http://one-inbox.test` — the app should load (without HTTPS — that's fine for this test)

---

#### B11 — Create the Nginx Tunnel Config

This file makes the app accessible through the Cloudflare tunnel.

1. Open File Explorer and go to:
   `C:\Users\USERNAME\.config\herd\config\pro\nginx\`
   (Replace `USERNAME` with the actual Windows username on this PC, e.g. `Omar`)
2. Create a new file called `tunnel.conf` in that folder (right-click → New → Text Document → rename it to `tunnel.conf`, make sure it's not `tunnel.conf.txt`)
3. Open it with Notepad and paste this content:

```nginx
server {
    listen 127.0.0.1:8088;
    server_name _;
    root /;
    charset utf-8;
    client_max_body_size 512M;

    location ~* /41c270e4-5535-4daa-b23e-c269744c2f45/([A-Z]+:)(.*) {
        internal;
        alias $1;
        try_files $2 $2/;
    }

    location ~* ^/(app|apps)(/|$) {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $http_host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_read_timeout 86400;
    }

    location / {
        rewrite ^ "C:/Program Files/Herd/resources/app.asar.unpacked/resources/valet/server.php" last;
    }

    error_page 404 "C:/Program Files/Herd/resources/app.asar.unpacked/resources/valet/server.php";
    error_log  "C:/Users/USERNAME/.config/herd/Log/nginx-error.log";
    access_log off;

    location ~ [^/]\.php(/|$) {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass $herd_sock;
        fastcgi_index "C:/Program Files/Herd/resources/app.asar.unpacked/resources/valet/server.php";
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME "C:/Program Files/Herd/resources/app.asar.unpacked/resources/valet/server.php";
        fastcgi_param HERD_HOME      "C:/Users/USERNAME/.config/herd";
        fastcgi_param HTTP_HOST      "one-inbox.test";
        fastcgi_param SERVER_NAME    "one-inbox.test";
        fastcgi_param PATH_INFO      $fastcgi_path_info;
        fastcgi_param HTTPS          "on";
    }

    location ~ /\.ht {
        deny all;
    }
}
```

4. Replace both occurrences of `USERNAME` in the file with the actual Windows username (e.g. `Omar`)
5. Save and close the file
6. Right-click the **Herd tray icon** → click **Restart Services**

**How to verify:** Open PowerShell and run:
```powershell
Test-NetConnection -ComputerName 127.0.0.1 -Port 8088
```
You should see `TcpTestSucceeded : True`

---

#### B12 — Set Up the Cloudflare Tunnel

1. Open a browser and go to:
   `https://github.com/cloudflare/cloudflared/releases/latest`
2. Find `cloudflared-windows-amd64.exe` and download it
3. Create a folder: `C:\cloudflared\`
4. Move the downloaded file into `C:\cloudflared\` and rename it to `cloudflared.exe`
5. Add it to PATH so you can run it from anywhere:
   - Press Win+S → search **"Environment Variables"** → click it
   - Click **Environment Variables** button at the bottom
   - Under **System variables**, find **Path** → click it → click **Edit**
   - Click **New** → type `C:\cloudflared` → click OK → OK → OK
   - Close and reopen any PowerShell windows

6. Create the config folder:
```powershell
mkdir C:\Users\$env:USERNAME\.cloudflared
```

7. Copy the tunnel credentials from Server A. On Server A, find this file:
   `C:\Users\NanoChip\.cloudflared\c49ffd21-f8da-4c8c-af4d-296f5551d04f.json`
   Copy it to Server B at: `C:\Users\USERNAME\.cloudflared\c49ffd21-f8da-4c8c-af4d-296f5551d04f.json`

8. Create the config file at `C:\Users\USERNAME\.cloudflared\config.yml`:
   Open Notepad, paste this, replace `USERNAME` with the real username, save it there:
```yaml
tunnel: c49ffd21-f8da-4c8c-af4d-296f5551d04f
credentials-file: C:\Users\USERNAME\.cloudflared\c49ffd21-f8da-4c8c-af4d-296f5551d04f.json
ingress:
  - hostname: ot1-pro.com
    service: http://127.0.0.1:8088
  - service: http_status:404
```

9. Open PowerShell **as Administrator** and run:
```powershell
cloudflared --config "C:\Users\$env:USERNAME\.cloudflared\config.yml" service install
Start-Service cloudflared
```

**How to verify:**
```powershell
Get-Service cloudflared
```
Status must say `Running`.

Then open `https://ot1-pro.com` on your **phone** (not on Server B). If it loads, the tunnel is working and Cloudflare is routing some traffic to Server B.

---

#### B13 — Install Queue Worker as a Windows Service

Open PowerShell **as Administrator**:

First, download NSSM:
1. Go to `https://nssm.cc/download`
2. Download the zip → extract it → find `nssm.exe` inside the `win64` folder
3. Copy `nssm.exe` to `C:\tools\`
4. Add `C:\tools\` to PATH the same way you did in Step B12 (point 5)
5. Open a new PowerShell window

Now install the service:
```powershell
$php = "C:\Program Files\Herd\resources\app.asar.unpacked\resources\bin\php\php.exe"
$app = "C:\Users\$env:USERNAME\Herd\one-inbox"

nssm install OneInboxQueue $php "$app\artisan queue:work --sleep=3 --tries=3 --max-time=3600"
nssm set OneInboxQueue AppDirectory $app
nssm set OneInboxQueue AppStdout "$app\storage\logs\queue.log"
nssm set OneInboxQueue AppStderr "$app\storage\logs\queue-error.log"
nssm set OneInboxQueue Start SERVICE_AUTO_START
nssm start OneInboxQueue
```

**How to verify:**
```powershell
nssm status OneInboxQueue
```
Should say `SERVICE_RUNNING`

> Server B does NOT run Reverb or the Scheduler — only Server A does.

---

#### B14 — Set Up DDNS (So You Can Always Remote In)

Your internet IP changes randomly. This step makes `server-b.ot1-pro.com` always point to Server B's current IP so you can RDP into it from anywhere.

**Step 1 — Create a DNS record in Cloudflare:**
1. Go to `https://dash.cloudflare.com` → click `ot1-pro.com` → click **DNS**
2. Click **Add record**
   - Type: `A`
   - Name: `server-b`
   - IPv4: go to `https://ifconfig.me` on Server B and paste the IP shown
   - Proxy: **OFF** (click the orange cloud until it turns grey)
3. Click Save

**Step 2 — Create a PowerShell update script on Server B:**

Create a file at `C:\tools\ddns-update.ps1` with this content.
Replace `YOUR_CF_API_TOKEN` with a Cloudflare API token (create at Cloudflare → My Profile → API Tokens → Create Token → use "Edit zone DNS" template).
Replace `YOUR_ZONE_ID` with the Zone ID shown on your Cloudflare dashboard → ot1-pro.com → Overview → right sidebar.
Replace `YOUR_RECORD_ID` by running this in PowerShell after creating the DNS record:
```powershell
Invoke-RestMethod -Uri "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID/dns_records?name=server-b.ot1-pro.com" -Headers @{"Authorization"="Bearer YOUR_CF_API_TOKEN"}
```
Copy the `id` field from the result.

```powershell
$ip     = (Invoke-WebRequest -Uri "https://ifconfig.me" -UseBasicParsing).Content.Trim()
$zone   = "YOUR_ZONE_ID"
$record = "YOUR_RECORD_ID"
$token  = "YOUR_CF_API_TOKEN"
$body   = @{ type="A"; name="server-b.ot1-pro.com"; content=$ip; ttl=60 } | ConvertTo-Json
Invoke-RestMethod -Uri "https://api.cloudflare.com/client/v4/zones/$zone/dns_records/$record" `
    -Method PUT -Headers @{"Authorization"="Bearer $token"; "Content-Type"="application/json"} -Body $body
```

**Step 3 — Schedule it to run every 5 minutes:**
1. Press Win+S → search **Task Scheduler** → open it
2. Click **Create Basic Task** on the right
3. Name: `OT1 Pro DDNS Update` → Next
4. Trigger: **Daily** → Next → set start time → Next
5. Action: **Start a program** → Next
   - Program: `powershell.exe`
   - Arguments: `-NonInteractive -File C:\tools\ddns-update.ps1`
6. Finish
7. Find the task in the list → right-click → **Properties** → **Triggers** tab → Edit → check **Repeat task every: 5 minutes** → OK

**Step 4 — Enable Remote Desktop on Server B:**
1. Press Win+S → search **Remote Desktop settings** → open it
2. Toggle **Enable Remote Desktop** to ON
3. To connect from Server A: open Remote Desktop Connection → type `server-b.ot1-pro.com`

---

#### ✅ Server B is Done — Final Checklist

Before moving on, verify all of these:

| Check | How to verify |
|-------|--------------|
| App loads | Open `https://ot1-pro.com` on your phone — it should work even if you shut down Server A |
| Queue worker running | `nssm status OneInboxQueue` → `SERVICE_RUNNING` |
| Cloudflare tunnel running | `Get-Service cloudflared` → `Running` |
| DB connection works | Open the app → try logging in — if login works, DB is connected |
| Redis connection works | App loads without errors (sessions use Redis) |
| Can RDP in | From another PC: Remote Desktop → `server-b.ot1-pro.com` |

---

#### Server C

Server C is exactly the same as Server B. Repeat all of Part 2 on Server C.
The only differences:
- In Step B7: `.env` DB_HOST and REDIS_HOST still point to **Server A's IP** (same as B)
- In Step B12: `config.yml` is identical (same tunnel ID)
- In Step B14: create DNS record `server-c` instead of `server-b`, update the DDNS script name accordingly

**Step A1 — Enable MySQL in Herd on Server A**

1. Open Laravel Herd → click the tray icon → go to **Services**
2. Turn on **MySQL** — Herd will start MySQL on port 3306
3. Open a terminal on Server A and run:
   ```powershell
   # Connect to MySQL (Herd's MySQL has no password by default)
   "C:\Program Files\Herd\resources\app.asar.unpacked\resources\bin\mysql\bin\mysql.exe" -u root
   ```
4. Run these SQL commands inside MySQL:
   ```sql
   CREATE DATABASE one_inbox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'one_inbox'@'localhost' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
   CREATE USER 'one_inbox'@'%' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
   GRANT ALL ON one_inbox.* TO 'one_inbox'@'localhost';
   GRANT ALL ON one_inbox.* TO 'one_inbox'@'%';
   FLUSH PRIVILEGES;
   EXIT;
   ```
5. Find Server A's **local IP address** — open PowerShell and run `ipconfig`, look for IPv4 Address (e.g. `192.168.1.100`). Write this down — Server B and C will need it.

**Step A2 — Allow Server B/C to reach MySQL through Windows Firewall**

1. Press Win+S → search **Windows Defender Firewall** → open it
2. Click **Advanced Settings** on the left
3. Click **Inbound Rules** → then **New Rule** on the right
4. Choose **Port** → Next → TCP, specific port: `3306` → Next
5. Choose **Allow the connection** → Next → check all three (Domain, Private, Public) → Next
6. Name it `MySQL for One Inbox` → Finish

**Step A3 — Enable Redis in Herd on Server A**

1. In Herd tray → Services → turn on **Redis**
2. Redis will run on port 6379
3. Add another Firewall rule the same way as above but for port `6379`, name it `Redis for One Inbox`

**Step A4 — Migrate the app from SQLite to MySQL on Server A**

1. Open `.env` on Server A and change the database section:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=one_inbox
   DB_USERNAME=one_inbox
   DB_PASSWORD=YOUR_STRONG_PASSWORD

   REDIS_HOST=127.0.0.1
   REDIS_PORT=6379
   ```
2. In a terminal inside the project folder, run:
   ```powershell
   php artisan migrate --force
   php artisan config:cache
   ```
3. Open `https://ot1-pro.com` and make sure everything still works before continuing.

---

### Setting Up Server B (repeat identically for Server C)

Work through these steps in order on the new Windows PC.

---

**Step 1 — Install Laravel Herd**

1. Go to `https://herd.laravel.com` and download the Windows installer
2. Install it and open it — it will appear in the system tray
3. In Herd settings, note where it serves sites from (default: `C:\Users\USERNAME\Herd\`)

---

**Step 2 — Install Git**

1. Go to `https://git-scm.com/download/win` and install Git
2. During install, choose "Git from the command line and also from 3rd-party software"

---

**Step 3 — Install Node.js**

1. Go to `https://nodejs.org` and download the LTS version
2. Install it with default settings

---

**Step 4 — Clone the App**

Open PowerShell and run:
```powershell
cd C:\Users\USERNAME\Herd
git clone https://github.com/YOUR_ORG/one-inbox.git one-inbox
cd one-inbox
```

Replace `YOUR_ORG` with your actual GitHub org/username. If the repo is private, you'll need to log in with `gh auth login` first (install GitHub CLI from `https://cli.github.com`).

---

**Step 5 — Install PHP Dependencies**

In the project folder (`C:\Users\USERNAME\Herd\one-inbox`), open PowerShell and run:
```powershell
composer install --no-dev --optimize-autoloader
```

If `composer` is not found, Herd installs it — find it at:
`"C:\Program Files\Herd\resources\app.asar.unpacked\resources\bin\composer.bat"`

Or install Composer from `https://getcomposer.org/Composer-Setup.exe`.

---

**Step 6 — Copy the .env File from Server A**

The `.env` file is not in git (it contains secrets). You need to copy it manually.

Option A — USB drive: Copy `C:\Users\NanoChip\Herd\one-inbox\.env` from Server A to a USB stick, paste it into the project folder on Server B.

Option B — over the network: On Server A, share the file via Windows file sharing, or use RDP to copy-paste.

Once copied to Server B, open the `.env` file on Server B and change only these values:

```env
# Point to Server A's IP for database and cache
DB_CONNECTION=mysql
DB_HOST=192.168.1.100        ← Server A's local IP (from Step A1)
DB_PORT=3306
DB_DATABASE=one_inbox
DB_USERNAME=one_inbox
DB_PASSWORD=YOUR_STRONG_PASSWORD

REDIS_HOST=192.168.1.100     ← Server A's local IP
REDIS_PORT=6379

# Keep everything else the same as Server A
# Same APP_KEY, same REVERB settings, same META keys, etc.
APP_DEBUG=false
```

---

**Step 7 — Build the Frontend Assets**

```powershell
npm install
npm run build
```

---

**Step 8 — Cache the Config**

```powershell
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

> Do NOT run `php artisan migrate` on Server B. Server A handles all database migrations.

---

**Step 9 — Set Up the Nginx Tunnel Config on Server B**

Server B needs the same port 8088 nginx vhost that Server A has. This is what makes Herd serve the app correctly when the Cloudflare tunnel connects.

1. Find Herd's nginx config folder — it's at:
   `C:\Users\USERNAME\.config\herd\config\pro\nginx\`
2. Create a new file called `tunnel.conf` in that folder
3. Paste this content into it (this is the exact same file as Server A):

```nginx
server {
    listen 127.0.0.1:8088;
    server_name _;
    root /;
    charset utf-8;
    client_max_body_size 512M;

    location ~* /41c270e4-5535-4daa-b23e-c269744c2f45/([A-Z]+:)(.*) {
        internal;
        alias $1;
        try_files $2 $2/;
    }

    location ~* ^/(app|apps)(/|$) {
        proxy_pass http://127.0.0.1:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $http_host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_read_timeout 86400;
    }

    location / {
        rewrite ^ "C:/Program Files/Herd/resources/app.asar.unpacked/resources/valet/server.php" last;
    }

    error_page 404 "C:/Program Files/Herd/resources/app.asar.unpacked/resources/valet/server.php";
    error_log  "C:/Users/USERNAME/.config/herd/Log/nginx-error.log";
    access_log off;

    location ~ [^/]\.php(/|$) {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass $herd_sock;
        fastcgi_index "C:/Program Files/Herd/resources/app.asar.unpacked/resources/valet/server.php";
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME "C:/Program Files/Herd/resources/app.asar.unpacked/resources/valet/server.php";
        fastcgi_param HERD_HOME      "C:/Users/USERNAME/.config/herd";
        fastcgi_param HTTP_HOST      "one-inbox.test";
        fastcgi_param SERVER_NAME    "one-inbox.test";
        fastcgi_param PATH_INFO      $fastcgi_path_info;
        fastcgi_param HTTPS          "on";
    }

    location ~ /\.ht {
        deny all;
    }
}
```

> Replace `USERNAME` with the actual Windows username on Server B (e.g. `Omar`).

4. In Herd, right-click the tray icon → **Restart Services** to reload nginx

---

**Step 10 — Tell Herd to Serve the App**

In Herd's tray menu → **Sites** → the app folder should already appear since it's inside the Herd directory. If not, click **Add Site** and point it to `C:\Users\USERNAME\Herd\one-inbox`.

---

**Step 11 — Set Up the Cloudflare Tunnel on Server B**

The tunnel connects Server B to the same `ot1-pro.com` as Server A. Cloudflare will automatically load balance between both.

1. Download `cloudflared.exe` from:
   `https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-windows-amd64.exe`
2. Rename it to `cloudflared.exe` and put it in `C:\cloudflared\`
3. Add `C:\cloudflared\` to your Windows PATH (search "Environment Variables" → edit Path → add it)
4. Create the config folder:
   ```powershell
   mkdir C:\Users\USERNAME\.cloudflared
   ```
5. Copy the tunnel credentials JSON file from Server A. On Server A it's at:
   `C:\Users\NanoChip\.cloudflared\c49ffd21-f8da-4c8c-af4d-296f5551d04f.json`
   Copy that file to the same path on Server B (same filename, same folder).
6. Create `C:\Users\USERNAME\.cloudflared\config.yml` with this content:
   ```yaml
   tunnel: c49ffd21-f8da-4c8c-af4d-296f5551d04f
   credentials-file: C:\Users\USERNAME\.cloudflared\c49ffd21-f8da-4c8c-af4d-296f5551d04f.json
   ingress:
     - hostname: ot1-pro.com
       service: http://127.0.0.1:8088
     - service: http_status:404
   ```
7. Open PowerShell **as Administrator** and install it as a Windows service:
   ```powershell
   cloudflared --config "C:\Users\USERNAME\.cloudflared\config.yml" service install
   Start-Service cloudflared
   ```
8. Verify it's running:
   ```powershell
   Get-Service cloudflared
   ```
   Status should say `Running`.

At this point, open `https://ot1-pro.com` from your phone (not from Server B itself). Cloudflare is now sending some traffic to Server B. If it loads, Server B is working.

---

**Step 12 — Install Queue Worker as a Windows Service (NSSM)**

Server B runs a queue worker. It does NOT run the scheduler (only Server A does).

1. Download NSSM from `https://nssm.cc/download` — get the 64-bit version
2. Put `nssm.exe` in `C:\tools\nssm\` and add that to your PATH
3. Open PowerShell **as Administrator**:
   ```powershell
   $php = "C:\Program Files\Herd\resources\app.asar.unpacked\resources\bin\php\php.exe"
   $app = "C:\Users\USERNAME\Herd\one-inbox"

   nssm install OneInboxQueue $php "$app\artisan queue:work --sleep=3 --tries=3 --max-time=3600"
   nssm set OneInboxQueue AppDirectory $app
   nssm set OneInboxQueue AppStdout "$app\storage\logs\queue.log"
   nssm set OneInboxQueue AppStderr "$app\storage\logs\queue-error.log"
   nssm set OneInboxQueue Start SERVICE_AUTO_START
   nssm start OneInboxQueue
   ```

---

**Step 13 — DDNS so You Can Always Remote Into Server B**

Since WE fiber gives you a dynamic IP, you need a way to SSH or RDP into Server B even when its IP changes.

1. In Cloudflare DNS, create an A record:
   - Name: `server-b` (becomes `server-b.ot1-pro.com`)
   - Value: Server B's current public IP (check `https://ifconfig.me` on Server B)
   - Proxy: **OFF** (grey cloud — this needs to be a direct IP, not proxied)
2. Create a PowerShell script at `C:\tools\ddns-update.ps1` on Server B:
   ```powershell
   $ip = (Invoke-WebRequest -Uri "https://ifconfig.me" -UseBasicParsing).Content.Trim()
   $headers = @{
       "Authorization" = "Bearer YOUR_CLOUDFLARE_API_TOKEN"
       "Content-Type"  = "application/json"
   }
   $body = @{
       type    = "A"
       name    = "server-b.ot1-pro.com"
       content = $ip
       ttl     = 60
   } | ConvertTo-Json
   Invoke-RestMethod -Uri "https://api.cloudflare.com/client/v4/zones/YOUR_ZONE_ID/dns_records/YOUR_RECORD_ID" `
       -Method PUT -Headers $headers -Body $body
   ```
   > Get your Zone ID from Cloudflare dashboard → Overview page (right sidebar).
   > Get your Record ID by calling: `GET /zones/ZONE_ID/dns_records?name=server-b.ot1-pro.com` via Cloudflare API.
3. Schedule it to run every 5 minutes via Task Scheduler:
   - Open Task Scheduler → Create Basic Task
   - Trigger: Daily, repeat every 5 minutes
   - Action: Start a program → `powershell.exe` → Arguments: `-File C:\tools\ddns-update.ps1`
4. Enable Remote Desktop on Server B (if not already):
   - Settings → System → Remote Desktop → turn it ON
   - Note the computer name or use the `server-b.ot1-pro.com` DNS name to RDP in

---

### Server C Setup

Server C is 100% identical to Server B. Follow all 13 steps above again on Server C.

The only difference: name things `server-c` instead of `server-b` in DNS and scripts.

Also, run this on Server A's MySQL to allow Server C's IP to connect:
```sql
-- Connect to MySQL on Server A first, then:
GRANT ALL ON one_inbox.* TO 'one_inbox'@'SERVER_C_IP' IDENTIFIED BY 'YOUR_STRONG_PASSWORD';
FLUSH PRIVILEGES;
```
(The `'%'` wildcard user we already created should cover this automatically, but being explicit is safer.)

---

### Deploying Updates to All Servers

When you push new code, you need to update all servers. Save this as `deploy.ps1` on your laptop:

```powershell
# deploy.ps1 — run this after pushing to git
# Requires: OpenSSH installed on each server, your SSH key added to each server

$servers = @(
    "USERNAME@server-a.ot1-pro.com",
    "USERNAME@server-b.ot1-pro.com",
    "USERNAME@server-c.ot1-pro.com"
)

$commands = @"
cd C:\Users\USERNAME\Herd\one-inbox
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
nssm restart OneInboxQueue
"@

# Deploy to all servers in parallel
$jobs = foreach ($server in $servers) {
    Start-Job -ScriptBlock {
        param($s, $c)
        ssh $s $c
    } -ArgumentList $server, $commands
}

$jobs | Wait-Job | Receive-Job
Write-Host "All servers updated."
```

> Note: For this script to work, you need OpenSSH set up on each server and your laptop's SSH key added to each server's `authorized_keys`. This is optional — you can also just RDP into each server and `git pull` manually until you set up SSH.

---

## 8. Architecture Diagram (Final Multi-Server)

```
Users (HTTPS)
     │
     ▼
┌─────────────────────────────────────┐
│         Cloudflare Edge             │
│  ot1-pro.com  (DDoS, WAF, TLS)     │
└────────────┬───────────┬────────────┘
             │           │
     (Tunnel A)    (Tunnel B / C)
             │           │
    ┌────────▼───┐  ┌────▼───────┐
    │  Server A  │  │  Server B  │  (Server C identical to B)
    │  (Primary) │  │  (Replica) │
    │            │  │            │
    │ Laravel    │  │ Laravel    │
    │ Reverb WS  │  │ Queue Work │
    │ Queue Work │  │ cloudflared│
    │ Scheduler  │  │            │
    │ cloudflared│  └────────────┘
    │            │
    │ ┌────────┐ │
    │ │ MySQL  │◄├──── Server B & C connect here
    │ │ Redis  │ │
    │ └────────┘ │
    └────────────┘
```

---

## 9. Migration Path: SQLite → MySQL (Do Before Adding Server B)

This is covered step by step in Section 7 → "Before You Start" (Steps A1–A4).

Short version:
1. Open Herd tray → Services → turn on MySQL
2. Connect to MySQL and create the `one_inbox` database and user (see Step A1 SQL commands)
3. Update `.env` on Server A: set `DB_CONNECTION=mysql`, `DB_HOST=127.0.0.1`, etc.
4. Run `php artisan migrate --force` and `php artisan config:cache`
5. Test the app still works before touching Server B

---

## Summary Checklist

### Done ✅
- [x] Cloudflare Tunnel installed as Windows service
- [x] `ot1-pro.com` routes to app via port 8088
- [x] Assets load from `https://ot1-pro.com` (no Private Network Access popup)
- [x] WebSocket (Reverb) routes through `wss://ot1-pro.com:443` (no Private Network Access popup)

### Do This Week ⚠️
- [ ] Set `APP_DEBUG=false`
- [ ] Rotate `META_WEBHOOK_VERIFY_TOKEN` to a random value
- [ ] Enable Cloudflare WAF (Managed Ruleset)
- [ ] Set up daily SQLite backup to OneDrive
- [ ] Install Reverb + Queue + Scheduler as NSSM Windows services
- [ ] Fix canonical URL (`URL::forceRootUrl` in AppServiceProvider)

### Before Adding Server B 📋
- [ ] Migrate SQLite → MySQL (Section 7 → Steps A1–A4)
- [ ] Get Server B PC powered on and connected to the same network as Server A
- [ ] Follow all 13 steps in Section 7 on Server B
- [ ] Set up DDNS Task Scheduler job on Server B (Step 13)
- [ ] Test Cloudflare load balancing: shut down Server A, verify Server B takes traffic within 30 seconds
