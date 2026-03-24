# OT1 Pro — Server Setup Guide
### For someone who has never set up a server before

> This guide walks you through turning a regular Windows PC into a live production server.
> Read everything once before starting. Follow every step in order. Do not skip anything.
> If something looks different on your screen than what's described, stop and ask before continuing.

---

## What You Need Before Starting

**Hardware:**
- A Windows 10 or Windows 11 PC (64-bit)
- At least 8GB RAM
- At least 50GB free storage
- Plugged into power (not running on battery)
- Connected to the internet via cable (Ethernet) — Wi-Fi works but cable is more stable

**Accounts you need (ask the person who sent you this for the passwords):**
- GitHub account credentials (or a Personal Access Token — they will give you this)
- Cloudflare API token (they will give you this)
- The `.env` file from the main server (they will send this to you via USB or file share)

**Tools to download (all free):**
You will download these during setup — links are provided at each step.

---

## PART 1 — Prepare the PC

---

### Step 1 — Check Windows Version

1. Press the **Windows key** on your keyboard (the key with the Windows logo ⊞)
2. Type **"winver"** and press Enter
3. A small window opens — it should say **Windows 10** or **Windows 11**
4. If it says anything older (Windows 7, Windows 8), stop — this PC cannot be used

---

### Step 2 — Make Sure Windows is Up to Date

1. Click the **Start menu** (Windows logo bottom-left)
2. Click **Settings** (the gear icon)
3. Click **Windows Update**
4. Click **Check for updates**
5. Install all available updates
6. Restart the PC if asked
7. Repeat until it says "You're up to date"

---

### Step 3 — Set Up Auto-Login (IMPORTANT — the server must restart itself)

This makes the PC automatically log in after a power outage or restart — without anyone needing to type a password. Without this, the server goes offline every time the PC restarts.

1. Press **Windows key + R** on your keyboard at the same time
2. A small "Run" box appears — type `netplwiz` and press Enter
3. A window called "User Accounts" opens
4. You will see a checkbox that says **"Users must enter a user name and password to use this computer"**
5. **Uncheck that checkbox** (click it to remove the checkmark)
6. Click **Apply**
7. A new window asks you to confirm — type the Windows login password for this PC and click OK
8. Click OK to close the User Accounts window
9. Restart the PC
10. The PC should boot all the way to the desktop **without asking for a password**

✅ **How to verify:** Restart the PC. If it goes straight to the desktop without a login screen, this step is done correctly.

---

### Step 4 — Name the PC (so you can find it on the network)

1. Click **Start** → **Settings** → **System** → **About**
2. Click **Rename this PC**
3. Name it `server-b` (or `server-c` if this is the third server)
4. Click Next → Restart now

---

### Step 5 — Connect to the Internet via Ethernet

1. Plug a network cable from the router/switch into the back of the PC
2. Make sure the network icon in the bottom-right of the screen shows a connected (not Wi-Fi) connection
3. Open a browser and go to `https://google.com` to confirm internet is working

---

### Step 6 — Find the PC's Local IP Address (write this down)

1. Right-click the **Start button**
2. Click **Windows PowerShell** (or **Terminal**)
3. Type `ipconfig` and press Enter
4. Look for the section for your network connection (Ethernet or Wi-Fi)
5. Find the line that says **IPv4 Address** — it will look like `192.168.1.XXX`
6. **Write this down** — you will need it later and the person who sent you this guide will need it too

---

### Step 7 — Find the PC's Public IP Address (write this down)

1. Open a browser and go to `https://ifconfig.me`
2. The page shows a single IP address — write it down
3. This is your public internet IP — it changes over time (that's why we set up DDNS later)

---

## PART 2 — Install Required Software

---

### Step 8 — Install Laravel Herd (the web server)

Herd is the software that runs the website. It includes everything needed: PHP, nginx, and other tools.

1. Open a browser and go to `https://herd.laravel.com`
2. Click the **Download for Windows** button
3. Run the installer that downloads
4. Click through the installer — keep all default settings
5. When it finishes, an **H icon** will appear in your **system tray** (bottom-right corner of the screen, near the clock — you may need to click the small arrow ^ to see it)
6. Click the H icon to make sure Herd opened correctly

✅ **How to verify:**
1. Right-click the **Start button** → click **Terminal** or **PowerShell**
2. Type `php --version` and press Enter
3. You should see something like `PHP 8.3.x` — if you see this, Herd is installed correctly

---

### Step 9 — Install Git

Git is used to download the app code from GitHub.

1. Go to `https://git-scm.com/download/win`
2. Click the download link for **64-bit Git for Windows**
3. Run the installer
4. On the screen that asks about PATH environment — choose: **"Git from the command line and also from 3rd-party software"**
5. On all other screens — keep the default settings and click Next
6. Click Install, then Finish

✅ **How to verify:**
1. Close any open PowerShell windows, then open a new one
2. Type `git --version` and press Enter
3. You should see something like `git version 2.x.x`

---

### Step 10 — Install Node.js

Node.js is used to build the app's visual interface (buttons, styles, etc.)

1. Go to `https://nodejs.org`
2. Click the big button on the left that says **LTS** (Long Term Support)
3. Run the installer — keep all default settings
4. Click through until Finish

✅ **How to verify:**
1. Close any open PowerShell windows, then open a new one
2. Type `node --version` and press Enter
3. You should see something like `v20.x.x`

---

### Step 11 — Install Composer

Composer downloads the PHP packages the app needs to run.

1. Go to `https://getcomposer.org/Composer-Setup.exe`
2. The file downloads automatically — run it
3. The installer will ask you to find the PHP executable
4. Click **Browse** and navigate to:
   `C:\Program Files\Herd\resources\app.asar.unpacked\resources\bin\php\`
5. Select `php.exe` from that folder
6. Continue through the installer with default settings

✅ **How to verify:**
1. Close any open PowerShell windows, then open a new one
2. Type `composer --version` and press Enter
3. You should see something like `Composer version 2.x.x`

---

### Step 12 — Install NSSM (keeps the app running in the background)

NSSM is a tool that turns commands into permanent Windows services — so the app keeps running even when no terminal window is open, and auto-starts after a reboot.

1. Go to `https://nssm.cc/download`
2. Under **Latest release**, click the download link (it will be a `.zip` file)
3. Once downloaded, right-click the zip file → **Extract All** → Extract
4. Open the extracted folder → open the folder called `win64`
5. You will see a file called `nssm.exe`
6. Create a new folder: open **File Explorer** → go to `C:\` → right-click → New → Folder → name it `tools`
7. Copy `nssm.exe` into `C:\tools\`

Now add `C:\tools\` to PATH so Windows can find nssm from any terminal:
1. Press **Windows key** → search **"Environment Variables"** → click **"Edit the system environment variables"**
2. Click the **Environment Variables** button at the bottom
3. In the bottom section (**System variables**), find the row called **Path** → click it → click **Edit**
4. Click **New** on the right side
5. Type `C:\tools` and press Enter
6. Click **OK** → **OK** → **OK** to close all windows

✅ **How to verify:**
1. Close any open PowerShell windows, then open a new one
2. Type `nssm version` and press Enter
3. You should see a version number

---

### Step 13 — Install cloudflared (connects this server to the internet)

Cloudflare Tunnel is what makes the website accessible from the internet without opening any firewall ports. It creates a secure connection from this PC to Cloudflare's network.

1. Go to this address in your browser:
   `https://github.com/cloudflare/cloudflared/releases/latest`
2. Scroll down to the **Assets** section
3. Find and click `cloudflared-windows-amd64.exe` to download it
4. Create a new folder: `C:\cloudflared\`
   (Open File Explorer → go to C:\ → right-click → New → Folder → name it `cloudflared`)
5. Move the downloaded `cloudflared-windows-amd64.exe` file into `C:\cloudflared\`
6. Rename it to `cloudflared.exe` (right-click → Rename → type `cloudflared.exe`)

Now add `C:\cloudflared\` to PATH (same process as Step 12):
1. Search **"Environment Variables"** → Edit system environment variables → Environment Variables
2. Under **System variables** → find **Path** → Edit → New
3. Type `C:\cloudflared` → OK → OK → OK

✅ **How to verify:**
1. Close any open PowerShell windows, then open a new one
2. Type `cloudflared --version` and press Enter
3. You should see a version number

---

## PART 3 — Set Up the App

---

### Step 14 — Download the App Code

1. Open **PowerShell** (right-click Start → Terminal or Windows PowerShell)
2. Type the following and press Enter (replace `YOUR_GITHUB_USERNAME` with the actual username the person gave you):

```
cd C:\Users\$env:USERNAME\Herd
git clone https://github.com/YOUR_GITHUB_USERNAME/one-inbox.git one-inbox
```

3. If asked to log in to GitHub:
   - Username: your GitHub username
   - Password: use the **Personal Access Token** the person gave you (NOT your GitHub password)
4. Wait for it to finish — you will see files downloading

✅ **How to verify:**
1. Type `dir C:\Users\$env:USERNAME\Herd\one-inbox` and press Enter
2. You should see a list of folders including `app`, `resources`, `routes`, `public`

---

### Step 15 — Copy the .env File

The `.env` file contains all the secret passwords and settings for the app. The person who sent you this guide will give it to you via USB stick or file sharing.

1. Get the `.env` file from them
2. Copy it into: `C:\Users\YOUR_WINDOWS_USERNAME\Herd\one-inbox\`
   (Replace YOUR_WINDOWS_USERNAME with the actual name — e.g. `C:\Users\Omar\Herd\one-inbox\`)
3. The file is called exactly `.env` — it starts with a dot and has no other extension

Now open the file and change two lines:
1. Right-click the `.env` file → **Open with** → **Notepad**
2. Find the line that says `DB_HOST=` and change it to the **local IP of Server A** (the person will tell you this IP — it looks like `192.168.1.XXX`)
3. Find the line that says `REDIS_HOST=` and change it to the **same IP**
4. Save the file (Ctrl+S) and close Notepad

---

### Step 16 — Install PHP Packages

1. Open **PowerShell**
2. Navigate to the app folder:
```
cd C:\Users\$env:USERNAME\Herd\one-inbox
```
3. Run:
```
composer install --no-dev --optimize-autoloader
```
4. Wait — this takes 1 to 3 minutes. You will see lots of text scrolling — that is normal.

✅ **How to verify:** Type `dir vendor` — you should see a folder called `laravel` in the list

---

### Step 17 — Build the App Interface

1. Still in the same PowerShell window, run:
```
npm install
```
2. Wait for it to finish (1–2 minutes)
3. Then run:
```
npm run build
```
4. Wait for it to finish (1–3 minutes)

✅ **How to verify:** Type `dir public\build` — you should see a folder called `assets`

---

### Step 18 — Cache the App Config

This makes the app start faster and run more efficiently.

1. Still in the same PowerShell window, run each line one at a time:
```
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Each command should say "Configuration cached successfully" or similar.

> ⚠️ Do NOT run `php artisan migrate` — only the main server (Server A) does that.

---

### Step 19 — Tell Herd to Serve the App

1. Click the **H icon** in the system tray (bottom-right)
2. Click **Sites**
3. The `one-inbox` folder should already appear in the list
4. If it does not appear: click **Add Site** and browse to `C:\Users\YOUR_USERNAME\Herd\one-inbox`

✅ **How to verify:**
1. Open a browser on this PC
2. Go to `http://one-inbox.test`
3. The app login page should appear (it won't have HTTPS yet — that's fine for this step)

---

### Step 20 — Create the Nginx Tunnel Config File

This file is what makes the app work when accessed from the internet through Cloudflare.

1. Open **File Explorer**
2. In the address bar at the top, paste this path and press Enter:
   `C:\Users\YOUR_USERNAME\.config\herd\config\pro\nginx\`
   (Replace YOUR_USERNAME with your actual Windows username)
3. Right-click in the empty space inside that folder → **New** → **Text Document**
4. Name it `tunnel.conf`
   - Important: make sure it is NOT saved as `tunnel.conf.txt`
   - If you see `.txt` at the end, you need to enable file extensions: in File Explorer → View → check "File name extensions" → then rename the file to remove the `.txt`
5. Right-click `tunnel.conf` → **Open with** → **Notepad**
6. Delete everything in the file (if anything is there)
7. Paste the following content exactly:

```
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
    error_log  "C:/Users/YOUR_USERNAME/.config/herd/Log/nginx-error.log";
    access_log off;

    location ~ [^/]\.php(/|$) {
        fastcgi_split_path_info ^(.+\.php)(/.+)$;
        fastcgi_pass $herd_sock;
        fastcgi_index "C:/Program Files/Herd/resources/app.asar.unpacked/resources/valet/server.php";
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME "C:/Program Files/Herd/resources/app.asar.unpacked/resources/valet/server.php";
        fastcgi_param HERD_HOME      "C:/Users/YOUR_USERNAME/.config/herd";
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

8. In the file you just pasted, replace **every occurrence** of `YOUR_USERNAME` with your actual Windows username (e.g. `Omar`)
   - There are 2 places where it says `YOUR_USERNAME` — change both
9. Save the file (Ctrl+S) and close Notepad
10. Right-click the **H icon** in the system tray → click **Restart Services**

✅ **How to verify:**
1. Open PowerShell and run:
```
Test-NetConnection -ComputerName 127.0.0.1 -Port 8088
```
2. You should see `TcpTestSucceeded : True`
   If you see `False`, the file was not saved correctly — go back and check Step 20

---

## PART 4 — Connect to the Internet

---

### Step 21 — Set Up the Cloudflare Tunnel

This connects the PC to the internet through Cloudflare. You will need the two files the person gave you:
- A file called `c49ffd21-f8da-4c8c-af4d-296f5551d04f.json` (the tunnel credentials)

1. Create the config folder — open PowerShell and run:
```
mkdir C:\Users\$env:USERNAME\.cloudflared
```

2. Copy the `.json` file they gave you into:
   `C:\Users\YOUR_USERNAME\.cloudflared\`

3. Create the config file:
   - Open Notepad
   - Paste the following content:
```
tunnel: c49ffd21-f8da-4c8c-af4d-296f5551d04f
credentials-file: C:\Users\YOUR_USERNAME\.cloudflared\c49ffd21-f8da-4c8c-af4d-296f5551d04f.json
ingress:
  - hostname: ot1-pro.com
    service: http://127.0.0.1:8088
  - service: http_status:404
```
   - Replace `YOUR_USERNAME` with your actual Windows username (2 places)
   - Click **File** → **Save As**
   - Navigate to: `C:\Users\YOUR_USERNAME\.cloudflared\`
   - File name: `config.yml`
   - Save as type: **All Files** (important — otherwise it saves as `config.yml.txt`)
   - Click Save

4. Open PowerShell **as Administrator**:
   - Right-click the **Start button**
   - Click **Windows PowerShell (Admin)** or **Terminal (Admin)**
   - A blue/dark window opens asking "Do you want to allow..." → click **Yes**

5. Run these two commands one at a time:
```
cloudflared --config "C:\Users\$env:USERNAME\.cloudflared\config.yml" service install
```
Then:
```
Start-Service cloudflared
```

✅ **How to verify:**
```
Get-Service cloudflared
```
The **Status** column must say `Running`.

Then ask the person who sent you this guide to check if the website loads from their phone — if it does, this server is now live on the internet.

---

### Step 22 — Install the Background Services (Queue Worker)

This keeps the app processing messages in the background automatically.

1. Open PowerShell **as Administrator** (right-click Start → Terminal/PowerShell Admin)
2. Run the setup script that comes with the app:
```
powershell -ExecutionPolicy Bypass -File "C:\Users\$env:USERNAME\Herd\one-inbox\scripts\setup-nssm-services.ps1"
```
3. It will ask: **"Is this Server A (the primary server with MySQL/Redis)? [y/n]"**
   - Type `n` and press Enter (Server A is the main laptop — this is Server B or C)
4. Wait for it to finish

✅ **How to verify:**
```
nssm status OneInboxQueue
```
Should say `SERVICE_RUNNING`

---

## PART 5 — Remote Access Setup

---

### Step 23 — Enable Remote Desktop

This lets the person who manages the servers connect to this PC from anywhere to fix issues.

1. Click **Start** → **Settings**
2. Click **System**
3. Scroll down and click **Remote Desktop**
4. Toggle **Enable Remote Desktop** to **ON**
5. Click **Confirm**
6. Write down the **PC name** shown on this page (looks like `DESKTOP-XXXXXX`) — send it to the person who manages the servers

---

### Step 24 — Set Up Automatic IP Updates (DDNS)

Your internet IP address changes randomly. This step makes `server-b.ot1-pro.com` always point to this PC's current IP so the manager can always connect remotely.

The person who manages the servers will give you a file called `ddns-update.ps1`. Once you have it:

1. Create a folder `C:\tools\` if it doesn't exist already
2. Put `ddns-update.ps1` inside `C:\tools\`
3. Schedule it to run every 5 minutes:
   - Press **Windows key** → search **Task Scheduler** → open it
   - Click **Create Basic Task** on the right side
   - Name: `OT1 Pro DDNS Update` → click Next
   - Trigger: select **Daily** → click Next
   - Set the start time to any time → click Next
   - Action: select **Start a program** → click Next
   - Program/script: `powershell.exe`
   - Add arguments: `-NonInteractive -File C:\tools\ddns-update.ps1`
   - Click Next → Finish
4. Now make it repeat every 5 minutes:
   - Find `OT1 Pro DDNS Update` in the Task Scheduler list
   - Right-click it → **Properties**
   - Click the **Triggers** tab → click **Edit**
   - Check the box **Repeat task every:** → select **5 minutes**
   - In the **for a duration of** dropdown → select **Indefinitely**
   - Click OK → OK

✅ **How to verify:**
- Right-click the task → **Run**
- If no error window appears, it worked
- Wait 1 minute then ask the manager to check that `server-b.ot1-pro.com` resolves to this PC's public IP

---

## PART 6 — Final Checks

---

### Step 25 — Restart and Verify Everything Auto-Starts

This is the most important test. Everything must survive a reboot with zero human intervention.

1. Restart the PC (Start → Power → Restart)
2. The PC should:
   - Boot directly to the desktop without asking for a password *(from Step 3)*
   - Show the Herd H icon in the system tray automatically *(Herd auto-starts)*
3. Wait 2 minutes after the desktop appears
4. Open PowerShell and run these checks one by one:

```
# Check cloudflared tunnel is running
Get-Service cloudflared

# Check queue worker is running
nssm status OneInboxQueue

# Check nginx is listening on port 8088
Test-NetConnection -ComputerName 127.0.0.1 -Port 8088
```

All three should show `Running` / `SERVICE_RUNNING` / `TcpTestSucceeded : True`.

5. Ask the manager to confirm the website loads from their phone.

---

### Step 26 — Send This Information to the Manager

Once everything is working, send the following to the person who manages the servers:

- [ ] This PC's **local IP address** (from Step 6, e.g. `192.168.1.XXX`)
- [ ] This PC's **Windows username** (e.g. `Omar`)
- [ ] Screenshot of `Get-Service cloudflared` showing `Running`
- [ ] Screenshot of `nssm status OneInboxQueue` showing `SERVICE_RUNNING`
- [ ] Confirmation that the website loaded on your phone at `https://ot1-pro.com`

---

## Quick Reference — What Each Service Does

| Service | What it does | How to check |
|---------|-------------|--------------|
| **Herd** (H icon in tray) | Runs the web server (nginx + PHP). If this stops, the website goes down. | Click H icon — should open without errors |
| **cloudflared** (Windows Service) | Connects this PC to Cloudflare so the internet can reach it. | `Get-Service cloudflared` → Running |
| **OneInboxQueue** (Windows Service) | Processes background tasks (sending messages, AI responses). | `nssm status OneInboxQueue` → SERVICE_RUNNING |

---

## Common Problems and How to Fix Them

| Problem | What to do |
|---------|-----------|
| Website not loading | Check Herd is running (H icon in tray). Check `Get-Service cloudflared` is Running. |
| H icon not in tray after reboot | Open Start menu → search "Herd" → open it manually. Then re-do Step 3. |
| `php --version` not found after restart | Re-open PowerShell. If still missing, open Herd app and restart it. |
| `nssm status OneInboxQueue` says STOPPED | Run: `nssm start OneInboxQueue` |
| `Get-Service cloudflared` says Stopped | Run as Admin: `Start-Service cloudflared` |
| `Test-NetConnection` port 8088 says False | Right-click Herd tray icon → Restart Services. Wait 10 seconds and try again. |
| PC won't auto-login after reboot | Re-do Step 3. Make sure you unchecked the checkbox and entered the correct password. |

---

## Who to Contact

If something is not working and you cannot fix it using the table above, contact the system manager and tell them:
- Which step you are on
- What you see on your screen (take a screenshot)
- What error message appears (if any)

**Do not try to fix things by guessing — always ask first.**
