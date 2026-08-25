# SEO Auto-Indexing — Operations Runbook

Automatic ping to **IndexNow** (Bing/Yandex/DuckDuckGo) and **Google Indexing API** whenever a blog post is published or its content changes.

**Shipped:** 2026-07-17 (commit `9e6701f`)

---

## How it works

1. `App\Observers\PostObserver` listens to `Post::saved`.
2. Fires when `published_at` transitions from null → set (just-published), OR when title/content/excerpt/meta_title/meta_description changes on an already-published post.
3. Dispatches `PingSearchEnginesJob` with `afterCommit()` — waits for the DB transaction to close before queueing.
4. Job calls `IndexNowService::submit()` (one POST, all URLs) then `GoogleIndexingService::notify()` (one call per URL).

**Files:**
- `app/Observers/PostObserver.php`
- `app/Jobs/PingSearchEnginesJob.php`
- `app/Services/Seo/IndexNowService.php`
- `app/Services/Seo/GoogleIndexingService.php` — hand-rolled RS256 JWT, no `google/apiclient` dep
- `app/Console/Commands/SeoPingBlog.php` — `php artisan seo:ping-blog`
- Registered in `AppServiceProvider::boot()`

---

## Prod configuration

`/var/www/ot1-pro.com/.env`:

```
INDEXNOW_KEY=c4f1089d98282c9eee5042765ca25031
INDEXNOW_HOST=ot1-pro.com
GOOGLE_INDEXING_CREDENTIALS=/var/www/ot1-pro.com/storage/app/google-indexing.json
```

**Service account:** `ot1-pro-indexing@ot1-pro-indexing.iam.gserviceaccount.com`
Added as **Owner** in Search Console for `ot1-pro.com`. Indexing API rejects non-Owners with 403.

**IndexNow key file:** served at `https://ot1-pro.com/{key}.txt` by a route in `routes/web.php` — no static file to maintain.

---

## Common operations

### Watch live logs

```bash
ssh root@187.77.67.94 'tail -50 /var/www/ot1-pro.com/storage/logs/laravel.log | grep -iE "indexnow|indexing"'
```

Expected healthy output:
```
IndexNow submitted {"count":N,"status":202}
Google Indexing API notified {"url":"...","type":"URL_UPDATED"}
```

### Backfill every published post (one-time or after big content change)

```bash
ssh root@187.77.67.94 'sudo -u deploy bash -c "cd /var/www/ot1-pro.com && php artisan seo:ping-blog --sync"'
```

`--sync` runs inline (shows output). Without it, jobs go through the queue.

### Ping a single post by slug

```bash
ssh root@187.77.67.94 'sudo -u deploy bash -c "cd /var/www/ot1-pro.com && php artisan seo:ping-blog --slug=whatsapp-crm-complete-guide --sync"'
```

### Verify IndexNow key file is live

```bash
curl -s -o /dev/null -w "HTTP %{http_code}\n" https://ot1-pro.com/c4f1089d98282c9eee5042765ca25031.txt
```

Must return `HTTP 200`. If 404, `INDEXNOW_KEY` is unset or `config:cache` needs to re-run.

### Count successes vs failures over last N log lines

```bash
ssh root@187.77.67.94 'tail -500 /var/www/ot1-pro.com/storage/logs/laravel.log | grep -c "Google Indexing API notified"'
ssh root@187.77.67.94 'tail -500 /var/www/ot1-pro.com/storage/logs/laravel.log | grep -c "Google Indexing API failed"'
ssh root@187.77.67.94 'tail -500 /var/www/ot1-pro.com/storage/logs/laravel.log | grep -c "IndexNow submitted"'
ssh root@187.77.67.94 'tail -500 /var/www/ot1-pro.com/storage/logs/laravel.log | grep -c "IndexNow submission failed"'
```

### Refresh caches after any env change

```bash
ssh root@187.77.67.94 'sudo -u deploy bash -c "cd /var/www/ot1-pro.com && php artisan config:cache && php artisan queue:restart"'
```

`queue:restart` is critical — workers cache the `.env` in memory until restarted.

---

## Rotating the IndexNow key

If the key ever leaks, rotate:

```bash
NEW_KEY=$(php -r "echo bin2hex(random_bytes(16));")
ssh root@187.77.67.94 "sed -i 's|^INDEXNOW_KEY=.*|INDEXNOW_KEY=${NEW_KEY}|' /var/www/ot1-pro.com/.env"
ssh root@187.77.67.94 'sudo -u deploy bash -c "cd /var/www/ot1-pro.com && php artisan config:cache && php artisan queue:restart"'
```

Old submissions remain valid at Bing; the old `/{oldkey}.txt` URL will 404, which is fine — IndexNow only revalidates when the key changes on a new submission.

Also update local `.env` (`INDEXNOW_KEY=`) so dev testing works.

---

## Rotating the Google service-account key

1. Google Cloud Console → IAM → Service Accounts → `ot1-pro-indexing` → Keys → Add Key (JSON) → download.
2. Upload to prod:
   ```bash
   scp new-key.json root@187.77.67.94:/tmp/g.json
   ssh root@187.77.67.94 'mv /tmp/g.json /var/www/ot1-pro.com/storage/app/google-indexing.json && chown deploy:deploy /var/www/ot1-pro.com/storage/app/google-indexing.json && chmod 600 /var/www/ot1-pro.com/storage/app/google-indexing.json'
   ```
3. `config:cache` + `queue:restart` (see above).
4. Delete the old key in Google Cloud Console.

The service is stateless — `GoogleIndexingService` re-mints an access token from the new key on next call. Cached token TTL is 3300s (`Cache::forget('google_indexing_token')` if you want to force it earlier).

---

## Quotas

| Service | Limit | Where to check |
|---|---|---|
| IndexNow | No published limit (10 000 URLs/POST cap) | — |
| Google Indexing API | 200 URLs/day per Cloud project (default) | https://console.cloud.google.com/apis/api/indexing.googleapis.com/quotas |

Bumping the Google quota requires a project justification via the Cloud Console. Prod-blog volume of <10 posts/day is nowhere near this.

---

## Troubleshooting

### `Google Indexing API failed` with 403

Service-account email is not an **Owner** in Search Console (User-level is insufficient). Re-add at https://search.google.com/search-console/users with **Owner** role.

### `Google Indexing API failed` with 429

Daily quota exhausted. Wait 24h or request a quota increase. Observer will keep dispatching — jobs will retry (3 tries, 60s backoff) then die. Rerun `seo:ping-blog --sync` the next day.

### `IndexNow submission failed` with 403

Key file doesn't resolve. Run the curl verification. Usually means `config:cache` wasn't run after setting `INDEXNOW_KEY`.

### `IndexNow submission failed` with 422

Same URL submitted too many times in a short window. Not a bug — IndexNow rate-limits per-URL. Ignore.

### Observer not firing when I publish

Check `queue:restart` ran after the last deploy. Check `laravel.log` for the specific post slug:

```bash
ssh root@187.77.67.94 'grep "your-post-slug" /var/www/ot1-pro.com/storage/logs/laravel.log'
```

If no entries: the DB transaction may not have committed (`afterCommit()` won't fire) or `published_at` was already set on a prior save.

---

## Manual bypass (if the automation is ever broken)

Google Search Console → URL Inspection → paste URL → Request Indexing. Limit ~10/day, per URL. Bing Webmaster Tools → Submit URL is the Bing equivalent.
