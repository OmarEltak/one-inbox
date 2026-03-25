# One Inbox — Troubleshooting & Known Issues

---

## CRITICAL: Two Separate Directories

| Environment | Directory | Database |
|-------------|-----------|----------|
| **Dev** (one-inbox.test) | `C:\Users\NanoChip\Herd\one-inbox` | `one-inbox\database\database.sqlite` |
| **Production** (ot1-pro.com) | `C:\Users\NanoChip\Herd\one-inbox-prod` | `one-inbox-prod\database\database.sqlite` |

**Always run artisan commands in the correct directory.**
Mistakes here waste a lot of time.

---

## Issue 1: Login fails — "These credentials do not match our records"

### Cause
- Password was reset in the **dev** directory, not **prod**
- OR the production database had no users at all (empty DB)

### Solution
```bash
cd C:\Users\NanoChip\Herd\one-inbox-prod

# Reset password
php artisan tinker --execute="
\$user = \App\Models\User::where('email', 'you@example.com')->first();
\$user->password = \Illuminate\Support\Facades\Hash::make('newpassword');
\$user->save();
echo Hash::check('newpassword', \$user->password) ? 'OK' : 'FAIL';
"
```

### If no users exist in prod DB
```bash
cd C:\Users\NanoChip\Herd\one-inbox-prod
php artisan tinker --execute="
\$user = \App\Models\User::create([
    'name' => 'Omar Eltak',
    'email' => 'omareltak7@gmail.com',
    'password' => \Illuminate\Support\Facades\Hash::make('password'),
    'email_verified_at' => now(),
]);
\$team = \App\Models\Team::create([
    'name' => 'Omar Team',
    'slug' => 'omar-team',
    'owner_id' => \$user->id,
    'personal_team' => true,
]);
\$user->update(['current_team_id' => \$team->id]);
\$user->teams()->attach(\$team->id);
echo 'Done';
"
```

---

## Issue 2: Artisan/tinker changes don't affect production

### Cause
Running commands in `one-inbox` (dev) instead of `one-inbox-prod` (prod).

### Solution
Always `cd` to the correct directory first:
```bash
# For production changes:
cd C:\Users\NanoChip\Herd\one-inbox-prod

# For dev/local changes:
cd C:\Users\NanoChip\Herd\one-inbox
```

---

## Issue 3: Database browser (Adminer)

### Setup
Adminer runs via PHP's built-in server on port 8282:
```bash
nohup php -S 127.0.0.1:8282 -t "C:\Users\NanoChip\Herd\one-inbox\public" > /dev/null 2>&1 &
```

Then open: `http://127.0.0.1:8282/db.php`

Login:
- **System**: SQLite 3
- **Database**: `C:\Users\NanoChip\Herd\one-inbox-prod\database\database.sqlite` (prod) or `...\one-inbox\database\database.sqlite` (dev)
- Username / Password: leave blank

> `db.php` is at `public/db.php` — it's a wrapper around `adminer.php` that bypasses the passwordless restriction.

---

## Issue 4: Deploy only specific files

GitHub Actions deploys on every push to `main`. To push only specific files without deploying unrelated changes:

```bash
git add path/to/file1 path/to/file2
git commit -m "feat: description"
git stash   # stash other changes if remote is ahead
git pull --rebase origin main
git stash pop
git push origin main
```

---

## Issue 5: Team required fields

When creating a Team via tinker, these fields are required:
- `owner_id` (not `user_id`)
- `slug`
- `personal_team`

Missing any of these throws a `NOT NULL constraint failed` error.

---

## Architecture Reference

```
Browser → Cloudflare (ot1-pro.com)
       → cloudflared daemon (Windows service)
       → nginx port 8088
       → PHP-CGI (Herd)
       → C:\Users\NanoChip\Herd\one-inbox-prod
       → database\database.sqlite
```

Dev URL `one-inbox.test` → same PHP-CGI → `C:\Users\NanoChip\Herd\one-inbox` → separate SQLite.
