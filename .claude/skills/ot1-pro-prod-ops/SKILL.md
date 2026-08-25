---
name: ot1-pro-prod-ops
description: Use BEFORE any change to the ot1-pro.com production VPS (187.77.67.94) — installing packages, editing /var/www/ot1-pro.com/.env, running php artisan commands, starting/reloading services, editing files directly on the server, or debugging live 500s. Also use when about to answer "why is prod broken?" or "why did that just error 500?" — this skill contains the specific 500-shipping mistakes that have already burned real user trust on this repo and the exact commands that avoid them. Do NOT skip. Do NOT rationalize that "this change is small." One-line changes have already shipped user-visible 500s. Invoke every time.
---

# ot1-pro Production Ops Runbook

You are about to touch the production system that real users are hitting right now (`https://ot1-pro.com`). Every mistake here has a direct, visible cost — a 500 page, a broken pair, an angry Slack. Two specific classes of mistake have ALREADY shipped user-visible 500s in this repo. This skill's job is to stop the third.

Read the whole thing before your first prod command. Yes, it's a wall of text. That's on purpose.

## The 60-second summary (memorize)

Before touching prod, ANSWER these five questions out loud in chat:

1. **What am I changing?** (one sentence)
2. **What breaks if this fails halfway?** (one sentence)
3. **What's my rollback if it goes wrong?** (exact commands)
4. **Do any of my commands write files as root that PHP-FPM will read as www-data?** (yes/no)
5. **After I'm done, what's my "it still works" check?** (exact command)

If you can't answer question 3 in exact commands, STOP and think until you can.

## The four rules that would have prevented every 500 this repo has shipped

### Rule 1: `php artisan` commands that write cache MUST run as `deploy` — NEVER as root

**What happens when you break this rule:** `php artisan config:cache` run as root writes `bootstrap/cache/config.php` in root's execution context. PHP-FPM (running as `www-data`) then serves intermittent `Illuminate\Encryption\MissingAppKeyException` on ~1 in N requests. The error is non-deterministic — it depends on FPM worker cycling — so it looks like a phantom bug. **This has shipped two user-visible 500s in this repo already.** Twice.

**Commands that write cache (must be `sudo -u deploy`):**
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache`
- `php artisan event:cache`
- `php artisan optimize`
- `php artisan storage:link`
- Anything under `php artisan cache:*` that writes

**Correct pattern:**

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com \
  && sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan config:cache \
  && systemctl reload php8.4-fpm'
```

The `XDG_CONFIG_HOME=/tmp HOME=/tmp` shim is required because `deploy`'s $HOME isn't writable by artisan subprocesses (psysh will explode without it — real error you'll hit if you skip it: `Writing to directory /var/www/.config/psysh is not allowed`).

The `systemctl reload php8.4-fpm` clears opcache so FPM workers pick up the new cached config immediately. Skipping this leaves stale worker memory alive for ~2-15 minutes depending on `pm.max_requests`.

**For read-only artisan** (`tinker` queries that don't write), `sudo -u www-data ...` is fine and mirrors what FPM sees. Do NOT use `sudo -u www-data` for cache writes — it can't write to `bootstrap/cache/` on default perms.

### Rule 2: Editing tracked files directly on prod is a landmine — always go through git

**What happens when you break this rule:** You SSH in, edit `docker-compose.wuzapi.yml` (or any tracked file) with `sed`/`vim`/etc. Next auto-deploy runs `sudo -u deploy git pull` on prod, which aborts with:

```
error: Your local changes to the following files would be overwritten by merge:
    docker-compose.wuzapi.yml
Please commit your changes or stash them before you merge.
Aborting
```

Result: **every subsequent auto-deploy silently fails.** Prod HEAD drifts from `main` and nobody notices for hours because git status is buried behind SSH. This happened on 2026-08-23 — commits `a9bda97` and `482a0ba` were pushed but never deployed until manual resolution.

**Correct pattern:**

- Change tracked files locally, commit, push, wait for CI auto-deploy.
- If prod hotfix is truly urgent and you can't wait 30s for the round-trip: make the same edit BOTH on prod AND commit to local repo, then `git push`. When CI pulls, its version of the file will match the on-prod version and merge cleanly.
- For untracked prod-only files (like `.env`), see Rule 3.

**Verifying deploy actually happened:**

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com && git log --oneline -1'
# expected: your just-pushed commit SHA
```

If it's not your SHA, do NOT assume auto-deploy is slow — SSH in and `sudo -u deploy git pull origin main` manually. The CI hook fails silently more often than you'd think.

### Rule 3: `.env` edits need atomicity — always back up first, always cache-rebuild after

**What happens when you break this rule:** You `sed -i` the .env, halfway through a `cat >> .env`, the terminal drops, or your regex was slightly off. Now `.env` is corrupted and `config:cache` writes an APP_KEY-less cache (see Rule 1 for what that produces).

**Correct pattern:**

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com
  # 1. Timestamped backup
  cp .env .env.bak.$(date +%s)

  # 2. Make the edit (idempotent block replacement, NOT append)
  sed -i "/^# --- YourBlock ---/,/^# --- End YourBlock ---/d" .env
  cat >> .env <<EOF
# --- YourBlock ---
KEY_A=value_a
KEY_B=value_b
# --- End YourBlock ---
EOF

  # 3. Perms — always 600 owned by deploy (never root)
  chown deploy:deploy .env
  chmod 600 .env

  # 4. Verify the file is still valid before caching
  grep -c "^APP_KEY=" .env  # MUST return 1

  # 5. Rebuild cache as deploy + reload FPM (see Rule 1)
  sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan config:cache
  systemctl reload php8.4-fpm

  # 6. Verify config is healthy
  sudo -u www-data XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan tinker --execute="
    echo strlen(config(\"app.key\")) > 30 ? \"OK\" : \"BROKEN\";
  "
'
```

The `# --- YourBlock ---` sentinel pattern is what makes it idempotent — re-running the whole script replaces the block cleanly instead of appending duplicates.

Rollback: `mv /var/www/ot1-pro.com/.env.bak.{TIMESTAMP} /var/www/ot1-pro.com/.env` then re-run steps 3-6.

### Rule 4: Docker publishes ports past UFW — bind to 127.0.0.1 by default

**What happens when you break this rule:** You publish a container port with `- "8082:8080"` (implicit `0.0.0.0` bind). Docker inserts an ACCEPT rule directly into the `DOCKER-USER` iptables chain, bypassing UFW's rules. Your admin API is now exposed to the public internet on port 8082. UFW says the port is closed. It isn't.

**Verified on 2026-08-23** for prod Wuzapi: initial rollout, external `curl http://187.77.67.94:8082/admin/users` returned `HTTP 401` (Wuzapi auth challenge, proving reachability) despite UFW showing only 22/80/443/8080 open.

**Correct pattern in compose files:**

```yaml
services:
  yourservice:
    ports:
      # Bind to loopback ONLY. Docker bypasses UFW; bare "PORT:8080" exposes to internet.
      - "${SERVICE_HOST_BIND:-127.0.0.1}:${SERVICE_HOST_PORT:-8082}:8080"
```

**Verification (both should fail; local should succeed):**

```bash
# External (should be unreachable):
curl -sS -m 3 -o /dev/null -w "external HTTP %{http_code}\n" http://187.77.67.94:8082/... || echo unreachable
# Local (should succeed with proper auth):
ssh root@187.77.67.94 'curl -sS http://127.0.0.1:8083/...'
```

If external returns anything other than "unreachable" or a hard connection reset, the port is exposed — fix immediately.

## The pre-flight checklist for adding a new prod dependency

Ran through this on 2026-08-23 when installing Docker + Wuzapi. Every step matters.

Before running the first install command:

1. **Inventory current state** — what's already installed, what ports are used, what services are running:
   ```bash
   ssh root@187.77.67.94 '
     echo "===packages==="; dpkg -l | grep -E "docker|nginx|php|mysql" | head
     echo "===ports==="; ss -tlnp | grep -E "LISTEN"
     echo "===services==="; systemctl list-units --type=service --state=running --no-legend | head -20
     echo "===resources==="; free -h; df -h / | tail -1
   '
   ```

2. **Prove the new thing is isolated** — write down (in chat) what other services would keep working if the new thing died. If you can't name them all, you don't understand the topology yet.

3. **Have the rollback command ready before running install.** For Docker install: `apt-get remove -y docker-ce docker-ce-cli containerd.io`. For Wuzapi container: `docker compose -f docker-compose.wuzapi.yml down -v`. Write them BEFORE the forward command.

4. **Do the install. Verify version. Verify service state.** Do NOT proceed to configuration until installation is proven clean.

5. **Configure with least privilege first.** Bind to loopback, run as non-root user in container, no unnecessary env vars. Escalate only if verified functionally required.

6. **Deploy order matters:** infra → env → code → verify.
   - Install service and get it running with default config
   - Add env vars to Laravel `.env` (see Rule 3)
   - `sudo -u deploy` config:cache + reload FPM
   - Push app code that USES the new service
   - HTTP-check the app endpoint that exercises the integration

## The pre-deploy checklist for changing app code

Before `git push origin main` on a change touching anything user-facing:

1. **Have you tested locally?** If no, write down why not (some things can't be tested locally — that's fine, but say so).
2. **Are there queued jobs that will run with old code after your deploy?** If yes, note that `php artisan queue:restart` is required post-deploy.
3. **Are you touching Livewire components?** Views are recompiled on request but if a component's state schema changed, in-flight Livewire snapshots break — hard-refresh is required for any user with the page open. Warn the user.
4. **Are you touching webhook processors?** Old queued webhooks will run with new code — will they crash? Match the new code path to old payload shapes with a fallback.
5. **Grep for parallel code paths.** If you're modifying `SendPlatformMessage::sendViaWhatsApp`, ALSO grep for `SendAiResponse::sendViaWhatsApp` — this repo has known parallel send paths that need to change together (see `memory/project_whatsapp_parallel_send_paths.md`). Same principle applies to any dispatch site.
6. **Push. Watch for the deploy commit to arrive on prod (`git log --oneline -1`).** Do NOT report "shipped" until the SHA is on prod.

## The prod-500 debug playbook

When the user reports a 500 or you see one in logs:

1. **Get the actual exception** — don't guess:
   ```bash
   ssh root@187.77.67.94 'tail -300 /var/www/ot1-pro.com/storage/logs/laravel.log | grep -A2 "ERROR" | tail -60'
   ```

2. **`MissingAppKeyException`** is almost always Rule 1. Fix:
   ```bash
   ssh root@187.77.67.94 'cd /var/www/ot1-pro.com \
     && rm -f bootstrap/cache/config.php \
     && sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan config:cache \
     && systemctl reload php8.4-fpm'
   ```

3. **`SQLSTATE[HY001]: Out of sort memory`** on a Model query with `latest()` or `->orderBy()` means the sort buffer can't hold the temp result set (webhook_logs is huge on this prod). Query by primary key range instead: `->whereBetween('id', [$max-10, $max])->get()` — no sort needed.

4. **`recv() failed (104: Connection reset by peer)` in nginx from PHP-FPM** — FPM worker crashed mid-request. Almost always OOM or a fatal PHP error not caught. Check `journalctl -u php8.4-fpm --since "5 min ago"` for the worker segfault reason.

5. **After ANY fix, HTTP-verify:**
   ```bash
   ssh root@187.77.67.94 'curl -sS -o /dev/null -w "HTTP %{http_code}\n" -L https://ot1-pro.com/connections'
   # want: HTTP 200
   ```

## The maintenance mode escape hatch

For any risky sequence (multi-step .env edit + code deploy + cache rebuild + FPM reload), consider putting the app in maintenance mode first:

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com && sudo -u deploy php artisan down --render="errors::503"'
# ... your risky sequence ...
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com && sudo -u deploy php artisan up'
```

The user sees a clean 503 "we'll be right back" page for 30s instead of intermittent 500s. Use liberally — it's cheap.

## Where the exact secrets live

Never commit or paste these values. Get them from these locations only:

- Prod `.env`: `/var/www/ot1-pro.com/.env` (600, `deploy:deploy`). SSH in, `sudo -u deploy cat .env`.
- Prod DB password: `.env` -> `DB_PASSWORD`. MySQL user: `deploy@localhost`.
- Wuzapi admin token: `.env` -> `WUZAPI_ADMIN_TOKEN`. Also see `tasks/journal.md` key-locations table.
- Meta App secret: `.env` -> `META_APP_SECRET`. Also visible at developers.facebook.com console.

## Cross-references

- `tasks/journal.md` — chronological ops log. **Append to it after every prod change.**
- `docs/ARCHITECTURE.md` — the 11 non-negotiable pins from CLAUDE.md.
- `~/.claude/projects/C--Users-NanoChip-Herd-one-inbox/memory/feedback_prod_config_cache_user.md` — the config:cache lesson in memory form.
- `~/.claude/projects/C--Users-NanoChip-Herd-one-inbox/memory/project_whatsapp_parallel_send_paths.md` — the parallel send paths reminder.
- CLAUDE.md project instructions (top of every session).

## When you finish a prod change

Post a summary in chat with:

1. What SHA is now on prod (`git log --oneline -1` output).
2. What the "still works" verification was (exact command + exact output).
3. What the rollback command is (exact command), in case something breaks in the next hour.
4. What's in `.env.bak.*` on prod (list them; keep the most recent, delete the rest if there are more than 5).

**Then append to `tasks/journal.md`.** Every prod change gets a journal entry. No exceptions.
