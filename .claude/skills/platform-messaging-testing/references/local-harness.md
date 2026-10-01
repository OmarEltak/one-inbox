# Local harness (cloud container or dev box)

Everything here was run successfully on 2026-10-01 in a Claude Code cloud container.
Use it to reproduce pipeline / rendering / 500 bugs. It cannot reproduce Meta behaviour
(delivery, access levels) — for that use the prod diagnostic (SKILL §2).

## 1. Dependencies

The lockfile targets PHP 8.4; containers often have 8.3. Tests still run.

```bash
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist --ignore-platform-reqs --no-scripts
# if nette/utils fails ("reference is not a tree"): its locked commit was rewritten upstream
COMPOSER_ALLOW_SUPERUSER=1 composer update nette/utils --ignore-platform-reqs --no-scripts
git checkout composer.lock            # never commit the lockfile change
npm ci && npm run build               # without public/build, any full-page test 500s (Vite manifest)
```

## 2. Tests

```bash
vendor/bin/pest tests/Feature/Connections          # IG/FB connect flows (Http::fake)
vendor/bin/pest tests/Feature/SuperAdmin/PageDiagnosticTest.php
```

- Do **not** run the whole suite in one process: two files both declare `makeCampaign()`
  → fatal redeclare. Loop per file:
  `for f in $(find tests -name '*Test.php'); do vendor/bin/pest $f | grep Tests:; done`
- Before blaming your change, run the failing file on unmodified code
  (`git stash push -- <files>`); Auth/Dashboard/Settings files fail without a Vite build.
- Prove a regression test is real: stash the fix, the test must fail, unstash.

## 3. Run the real app on SQLite

```bash
cp .env.example .env && php artisan key:generate --force     # .env is gitignored
# in .env: DB_CONNECTION=sqlite, DB_DATABASE=/abs/path/inbox.sqlite, APP_URL=http://127.0.0.1:8000,
#          BROADCAST_CONNECTION=log, QUEUE_CONNECTION=database, SESSION_DRIVER=file,
#          META_APP_ID=local-app, META_APP_SECRET=local-test-secret   (dummy, for signed test webhooks)
touch /abs/path/inbox.sqlite && php artisan migrate --force
php artisan tinker --execute="require '.claude/skills/platform-messaging-testing/scripts/seed-demo-inbox.php';"
nohup php artisan serve --host=127.0.0.1 --port=8000 > serve.log 2>&1 &
```

Login: `demo@ot1.test` / `password` (super-admin, onboarding complete).

## 4. Simulate an inbound DM end-to-end (no Meta needed)

```bash
WH_PLATFORM=instagram WH_ENTRY=<page platform_page_id> WH_SENDER=777000 WH_TEXT="hello" \
  php artisan tinker --execute="require '.claude/skills/platform-messaging-testing/scripts/send-test-webhook.php';"
php artisan queue:work --once --queue=urgent
```

Expect `HTTP 200 EVENT_RECEIVED`, a new `webhook_logs` row, and a message on the page's
conversation. Variants worth testing: wrong `WH_ENTRY` (→ "No page found", `team_id` null),
Direct-IG path (`WH_PATH=/api/webhooks/meta-ig WH_SECRET_KEY=instagram_app_secret`),
Messenger (`WH_PLATFORM=facebook`). The script refuses non-local URLs.

## 5. Drive the browser

Chromium is preinstalled; use the global Playwright (`NODE_PATH=$(npm root -g) node x.js`,
`chromium.launch({ executablePath: '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' })`).
Pattern: goto `/login` → fill email/password → submit → goto `/inbox` → click
`button[wire\\:click^="selectConversation"]` → screenshot. Record Livewire POST statuses
via `page.on('response')` to catch 500s on actions (e.g. Lost/Converted).
For UI contrast work, compute WCAG ratios from `getComputedStyle` with alpha-blended
ancestor backgrounds — measured numbers beat eyeballing screenshots.

## 6. Comparing against an older version

```bash
git worktree add -f /tmp/wt <sha>
ln -s $PWD/vendor /tmp/wt/vendor && ln -s $PWD/node_modules /tmp/wt/node_modules
cp .env /tmp/wt/.env && sed -i 's#^APP_URL=.*#APP_URL=http://127.0.0.1:8001#' /tmp/wt/.env
(cd /tmp/wt && npm run build && php artisan serve --port=8001 &)
# ... then: git worktree remove --force /tmp/wt
```

Don't `pkill -f` with a pattern that also matches your own shell command.

## 7. What the container can't reach

ot1-pro.com, graph.facebook.com, developers.facebook.com and SSH to the VPS are blocked
by the egress proxy. WebSearch works; WebFetch to Meta docs doesn't. For prod evidence use
the diagnostic page (user opens it) and GitHub Actions deploy logs (GitHub MCP).
