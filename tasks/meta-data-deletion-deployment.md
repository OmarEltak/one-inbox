# Meta User Data Deletion — Deployment Checklist

**Built:** 2026-05-10
**Trigger:** Email from Facebook on 2026-05-09 requesting deletion of 10 user IDs.

---

## What was built

| Piece | Path |
|-------|------|
| Migration | `database/migrations/2026_05_10_031535_create_data_deletion_requests_table.php` |
| Model | `app/Models/DataDeletionRequest.php` |
| Shared deletion service | `app/Services/Compliance/MetaUserDataDeleter.php` |
| Async job | `app/Jobs/ProcessMetaDataDeletion.php` |
| Webhook controller | `app/Http/Controllers/Webhooks/MetaDataDeletionController.php` |
| Status page view | `resources/views/pages/data-deletion-status.blade.php` |
| Manual artisan command | `app/Console/Commands/MetaDeleteUsersCommand.php` |
| Privacy policy section 7a | `resources/views/pages/privacy.blade.php` |
| Routes | `routes/api.php`, `routes/web.php` |

---

## Endpoints

**POST** `https://ot1-pro.com/api/webhooks/meta/data-deletion`
Accepts `signed_request` form field. HMAC-SHA256 verified against `META_APP_SECRET`. On success returns:
```json
{
  "url": "https://ot1-pro.com/data-deletion/status/<40-char-code>",
  "confirmation_code": "<40-char-code>"
}
```

**GET** `https://ot1-pro.com/data-deletion/status/{code}`
Public status page. Shows pending / completed / failed.

---

## Deploy steps (production)

1. **Push code**
   ```bash
   git add -A && git commit -m "feat(compliance): Meta data deletion callback + audit"
   git push origin main
   ```

2. **On the production server**
   ```bash
   git pull
   php artisan migrate                    # creates data_deletion_requests
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   sudo systemctl restart oneinbox-queue  # or whichever supervisor controls queue:work
   ```

3. **Verify the existing deletion request file from yesterday's email**
   ```bash
   mkdir -p storage/app/meta-deletions
   cat > storage/app/meta-deletions/2026-05-09.txt <<EOF
   122177139428769170
   122295091034025924
   26636003999422192
   26630899833214094
   976882368203572
   35773370235595141
   878714915218655
   26184005167933300
   122242702310093079
   26017078894659724
   EOF

   php artisan meta:delete-users storage/app/meta-deletions/2026-05-09.txt --dry-run
   php artisan meta:delete-users storage/app/meta-deletions/2026-05-09.txt
   ```
   *(Local already processed 1 match — Ahmed AL Azhary, ID 26184005167933300. Production may have more.)*

4. **Add the callback URL to Meta App Dashboard**
   - Go to https://developers.facebook.com/apps/1469090344742803/app-review-types/details/
   - Settings → Basic → Advanced (or look under "Data Deletion Request URL" in some dashboards)
   - Set:
     - **Data Deletion Request URL:** `https://ot1-pro.com/api/webhooks/meta/data-deletion`
     - **Data Deletion Instructions URL** (alternative if you prefer manual): `https://ot1-pro.com/privacy#7a-data-deletion-meta-apps`
   - Save.

5. **Test the live endpoint** (optional but recommended)
   ```bash
   APP_SECRET="<your META_APP_SECRET>"
   PAYLOAD=$(php -r "
     \$p = json_encode(['algorithm'=>'HMAC-SHA256','user_id'=>'TEST_ID','issued_at'=>time()]);
     \$pb = rtrim(strtr(base64_encode(\$p),'+/','-_'),'=');
     \$sig = hash_hmac('sha256', \$pb, '$APP_SECRET', true);
     \$sb = rtrim(strtr(base64_encode(\$sig),'+/','-_'),'=');
     echo \$sb.'.'.\$pb;
   ")
   curl -X POST https://ot1-pro.com/api/webhooks/meta/data-deletion \
        --data-urlencode "signed_request=$PAYLOAD"
   # Expect: {"url":"https://ot1-pro.com/data-deletion/status/...","confirmation_code":"..."}
   ```

6. **Optional but recommended:** also configure a **Deauthorize Callback URL** in the same Meta dashboard panel — pointed at the same endpoint or a sibling. Meta calls that one when a user revokes the app, and treating revocation as deletion is the safest interpretation.

---

## Compliance notes

- Every callback hit creates a row in `data_deletion_requests` (status pending → completed/failed) — that's the audit trail Meta will ask for if they review the app.
- Manual `meta:delete-users` runs also write rows with `source=manual`.
- Logs to `storage/logs/laravel.log` under context key `meta_data_deletion`.
- Eloquent foreign-key cascades handle: contacts → contact_platform, conversations, messages, lead_score_events.

---

## Related

- Meta callback spec: https://developers.facebook.com/docs/development/create-an-app/app-dashboard/data-deletion-callback
- GDPR Art. 17 (Right to erasure)
- CCPA § 1798.105 (Right to delete)
- Meta Platform Terms § 3(d)(i)
