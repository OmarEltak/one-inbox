# OT1-Pro Server Guide
## Everything you need to know to manage the production server

---

## Our Stack at a Glance

| Thing | What it is | Where |
|---|---|---|
| **Server** | Ubuntu 24.04 VPS | IP `187.77.67.94` |
| **Domain** | ot1-pro.com | Cloudflare (proxy on) |
| **App** | Laravel 12 PHP app | `/var/www/ot1-pro.com/` |
| **Web server** | Nginx | Routes traffic to PHP |
| **PHP** | PHP 8.4-FPM | Runs the Laravel app |
| **Database** | MySQL 8 | DB name: `one_inbox` |
| **Queue worker** | systemd `one-inbox-queue` | Processes background jobs |
| **WebSockets** | systemd `one-inbox-reverb` | Real-time updates (port 8080) |
| **Scheduler** | crontab every minute | Laravel scheduled tasks |
| **Deploy user** | `deploy` | SSH user for deployments |
| **Error tracking** | Flare (flareapp.io) | Alerts you on every 500 |

---

## How to SSH Into the Server

SSH is like a remote terminal — you type commands and they run on the server.

### From Git Bash (Windows)
Open Git Bash and type:
```bash
ssh deploy@ot1-pro.com
```
You're in. To exit, type `exit`.

### If SSH times out or says "Network unreachable"
Your home internet or ISP might block port 22. Options:
- Try from a different network (phone hotspot)
- Use the VPS provider's web console (login to your VPS host website → find "Console" or "VNC")

---

## The 20 Things You Need to Know

### 1. How deploys work
You **never manually touch the server**. Just push code to GitHub:
```bash
git push origin main
```
GitHub Actions automatically SSHes in, pulls the code, installs packages, runs migrations, and restarts services. Takes ~30 seconds. Watch it at: github.com/OmarEltak/one-inbox/actions

### 2. How to check if the site is broken
- Visit https://ot1-pro.com — if you see a blank page or 500, something is wrong
- Check Flare at flareapp.io — every error is logged there with full details
- Check GitHub Actions — github.com/OmarEltak/one-inbox/actions — if the deploy failed, it shows in red

### 3. How to read the server logs
SSH in, then:
```bash
tail -f /var/www/ot1-pro.com/storage/logs/laravel.log
```
This streams live log output. Press Ctrl+C to stop.

To search for errors in the last 500 lines:
```bash
tail -500 /var/www/ot1-pro.com/storage/logs/laravel.log | grep -i error
```

### 4. How to restart the queue worker
The queue processes background jobs (AI responses, syncing messages, etc.). If it gets stuck:
```bash
ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan queue:restart"
```
Or restart the systemd service:
```bash
ssh deploy@ot1-pro.com "sudo systemctl restart one-inbox-queue"
```

### 5. How to restart Reverb (WebSockets)
Reverb powers real-time inbox updates. If users say the inbox isn't updating live:
```bash
ssh deploy@ot1-pro.com "sudo systemctl restart one-inbox-reverb"
```

### 6. How to check if services are running
```bash
ssh deploy@ot1-pro.com "sudo systemctl status one-inbox-queue one-inbox-reverb"
```
Look for `Active: active (running)` in green. If it says `failed`, restart it (see above).

### 7. How to change a .env variable in production
The `.env` file lives at `/var/www/ot1-pro.com/.env` on the server. **Never commit it to git.**

To add or change a value:
```bash
ssh deploy@ot1-pro.com "echo 'MY_KEY=my_value' >> /var/www/ot1-pro.com/.env && php /var/www/ot1-pro.com/artisan config:cache"
```
To edit an existing value:
```bash
ssh deploy@ot1-pro.com "sed -i 's/OLD_VALUE=.*/OLD_VALUE=new_value/' /var/www/ot1-pro.com/.env && php /var/www/ot1-pro.com/artisan config:cache"
```
Always run `php artisan config:cache` after changing `.env` or the app won't pick up the new values.

### 8. How to run a database migration manually
Migrations run automatically on deploy, but if you need to run one manually:
```bash
ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan migrate --force"
```

### 9. How to check the database
Use TablePlus on your PC with these settings:
- **Host**: `ot1-pro.com` (or `187.77.67.94`)
- **Port**: `3306`
- **User**: `one_inbox_user` (or whatever was set up)
- **Database**: `one_inbox`
- **SSH Host**: `187.77.67.94`, SSH User: `deploy`, SSH Key: your private key

### 10. How to clear the cache
If the app is showing stale data or config after a change:
```bash
ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan config:cache && php artisan route:cache && php artisan view:cache"
```
To clear everything (no cache at all — slower but useful for debugging):
```bash
ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan config:clear && php artisan cache:clear && php artisan route:clear && php artisan view:clear"
```

### 11. What to do when users report a 500 error
1. Open flareapp.io — the error is already there with full details
2. Find the exception, fix it in your local code
3. `git push origin main` to deploy the fix
4. You don't need to SSH at all for most bug fixes

### 12. How to put the site in maintenance mode
If you need to deploy something risky and want to show a "maintenance" page:
```bash
ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan down --retry=60"
```
Bring it back up:
```bash
ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan up"
```

### 13. How to check disk space
```bash
ssh deploy@ot1-pro.com "df -h"
```
The `/` partition should have at least 2GB free. Log files can grow large — check with:
```bash
ssh deploy@ot1-pro.com "du -sh /var/www/ot1-pro.com/storage/logs/"
```
To clear old logs:
```bash
ssh deploy@ot1-pro.com "truncate -s 0 /var/www/ot1-pro.com/storage/logs/laravel.log"
```

### 14. How to check server memory and CPU
```bash
ssh deploy@ot1-pro.com "free -h && uptime"
```
If memory is near 100%, restart PHP-FPM:
```bash
ssh deploy@ot1-pro.com "sudo systemctl restart php8.4-fpm"
```

### 15. How to fix "Permission denied" errors in logs
This means a file in `storage/` is owned by the wrong user. Fix:
```bash
ssh deploy@ot1-pro.com "sudo chown -R deploy:www-data /var/www/ot1-pro.com/storage /var/www/ot1-pro.com/bootstrap/cache && chmod -R 775 /var/www/ot1-pro.com/storage /var/www/ot1-pro.com/bootstrap/cache"
```

### 16. How to add a GitHub Actions secret
Some values (API keys, etc.) are stored as GitHub Secrets so they're never in the code:
1. Go to github.com/OmarEltak/one-inbox/settings/secrets/actions
2. Click "New repository secret"
3. Enter the name (e.g. `FLARE_KEY`) and value

Current secrets:
- `DEPLOY_HOST` — the server IP/hostname
- `DEPLOY_KEY` — SSH private key used by GitHub Actions
- `FLARE_KEY` — Flare error tracking key

### 17. How to renew the SSL certificate
SSL (HTTPS) auto-renews via Let's Encrypt/certbot every 90 days. If it fails:
```bash
ssh deploy@ot1-pro.com "sudo certbot renew --dry-run"
```
If that works, run without `--dry-run`. Nginx restarts automatically.

### 18. How to check Nginx errors
If the site is completely unreachable (not just a PHP error):
```bash
ssh deploy@ot1-pro.com "sudo tail -50 /var/log/nginx/error.log"
```
To restart Nginx:
```bash
ssh deploy@ot1-pro.com "sudo systemctl restart nginx"
```

### 19. How to run an Artisan command in production
Laravel's `artisan` is the command-line tool for the app. Always prefix with `cd /var/www/ot1-pro.com &&`:
```bash
# List all available commands
ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan list"

# Open a Tinker REPL (PHP interactive shell for the app)
ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan tinker"

# Give a user super-admin rights
ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan tinker --execute=\"App\Models\User::where('email','you@example.com')->update(['is_super_admin'=>true])\""
```

### 20. The golden rule
**Never edit files directly on the server.** Always:
1. Edit locally in `C:\Users\NanoChip\Herd\one-inbox\`
2. `git push origin main`
3. Wait 30 seconds for auto-deploy

The one exception is `.env` — that file is only on the server and is never committed to git.

---

## Quick Reference Card

```
Site down?          → Check flareapp.io first
Deploy code         → git push origin main
Restart queue       → ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan queue:restart"
Restart Reverb      → ssh deploy@ot1-pro.com "sudo systemctl restart one-inbox-reverb"
Clear cache         → ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan config:cache"
Fix permissions     → ssh deploy@ot1-pro.com "sudo chown -R deploy:www-data /var/www/ot1-pro.com/storage && chmod -R 775 /var/www/ot1-pro.com/storage"
Check logs          → ssh deploy@ot1-pro.com "tail -100 /var/www/ot1-pro.com/storage/logs/laravel.log"
Maintenance mode    → ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan down"
Back online         → ssh deploy@ot1-pro.com "cd /var/www/ot1-pro.com && php artisan up"
```

---

## Our Key URLs
- **Production app**: https://ot1-pro.com
- **GitHub repo**: https://github.com/OmarEltak/one-inbox
- **GitHub Actions** (deploy log): https://github.com/OmarEltak/one-inbox/actions
- **Flare** (error tracking): https://flareapp.io
- **Cloudflare** (DNS + proxy): https://dash.cloudflare.com
