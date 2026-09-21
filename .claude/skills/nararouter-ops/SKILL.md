---
name: nararouter-ops
description: Use BEFORE any change to the NaraRouter AI provider on ot1-pro — the model chains (text or vision), primary models, scoring model, API keys, reset window, cooldown, or alert email — whether you're editing NaraRouterProvider.php, SendAiResponse.php catch block, config/services.php, or the prod .env NARAROUTER_* block. ALSO use FIRST when the user reports "AI is slow", "AI is stupid", "AI stopped replying", "customer got a weak response", or "vision/image AI not working" — before proposing any fix, work through the diagnostic in §5. Codifies the two-chain (text + vision) architecture, per-chain reset semantics, cross-chain fallback rules, key rotation on 401/402/403/429, global 30-min cooldown behavior, and the model-quality-vs-quota-weight tradeoff specific to Nara's Free plan tier.
---

# NaraRouter Ops Runbook (ot1-pro) — v2 (two-chain edition)

The AI provider layer on ot1-pro talks to NaraRouter (`router.bynara.id`), an OpenAI-compatible chat completions router. **As of 2026-09-21 the provider runs two independent model chains (text + vision) with cross-chain fallback and a global cooldown on total exhaustion.** For the design in one page, load the companion skill `nararouter-two-chain`. This runbook is the operational side: diagnostics, safe change procedures, and common failures.

## 1. The 60-second summary (memorize)

Before touching anything, answer these five out loud:

1. **What am I changing?** (env var? which chain — text or vision? provider code? key rotation? cooldown value?)
2. **What breaks if this deploys and NaraRouter starts 5xx-ing?** (fallback still cascades within the chain? cross-chain fallback still fires? global cooldown still triggers? `SendAiResponse` still releases with delay?)
3. **What's my rollback?** (exact commands — usually `.env.bak.<ts>` restore + `config:cache` + queue reload)
4. **Do I need to test both keys after this change?** (any code touching keys, headers, or key rotation → yes)
5. **After I ship, what's my "AI still replies" check?** Send one inbound message (text + image if touching vision) and watch `laravel.log` for `NaraRouter reply` info lines with the expected `chain=` and `model=` fields.

If you can't answer 3 in exact commands, STOP and think until you can.

## 2. The five load-bearing invariants (do NOT break)

Pinned in `CLAUDE.md` pin #7 and `docs/ARCHITECTURE.md` §4. Each has burned prod at least once.

### Invariant A: Reset window measures from FIRST fallback, PER CHAIN

`markActiveModel($kind, ...)` and `markActiveKey($kind, ...)` PRESERVE the existing `reset_at` timestamp on every subsequent successful call. If you "optimize" this by refreshing `reset_at` on every success, the chain never returns to head-of-chain — the "reset every 5 hours" behaviour is silently disabled. Symptom: prod stays on a fallback model forever after one bad hour, or on the cross-fallback chain (vision when you sent text) after one text-chain outage.

### Invariant B: Per-chain cache keys, do NOT share

The four cache keys — `nararouter:failover_state:text`, `nararouter:failover_state:vision`, `nararouter:active_key_state:text`, `nararouter:active_key_state:vision` — must remain suffix-namespaced. If any refactor collapses them into a single shared key, text success poisons vision's start pointer (and vice versa) and cross-fallback becomes non-deterministic. The unit test `NaraRouterTwoChainTest::test('text primary success caches text active_model only, ...')` pins this.

### Invariant C: `coalesceRoles()` output is string-typed content

The Anthropic Messages API (which NaraRouter proxies) requires strict user/assistant alternation. `coalesceRoles()` guarantees this. Its output shape is `[{role: 'user'|'assistant', content: string}, ...]`. Callers depend on `content` being a string. If you switch to array-of-blocks (for multimodal / vision), you MUST also update every caller AND update the unit test at `tests/Unit/Services/Ai/NaraRouterCoalesceTest.php`. Do not partially convert.

### Invariant D: Status → action mapping is deliberate

| Status | Meaning | Action |
|---|---|---|
| 200 | success | mark model + key active (for this chain), return |
| 400 | payload / request bug | return `''`; retrying on any model or chain won't help |
| 401 | wrong / expired key | try next key, SAME model |
| 402 | insufficient credits (Free-tier quota) | try next key, SAME model |
| 403 | this key can't access this model | try next key, SAME model |
| 429 | rate-limit on this key | try next key, SAME model |
| 404 | model doesn't exist here | cascade to next MODEL, restart key rotation |
| 5xx / timeout | upstream flake on this model | cascade to next MODEL, restart key rotation |

Do NOT add "retry-with-backoff on 5xx" — the model chain IS the retry. Do NOT "give up on 429" — the second key is the retry.

### Invariant E: Total exhaustion sets cooldown BEFORE throwing

When every (chain × model × key) attempt fails within one `dispatch()`, we (a) set `Cache::put('nararouter:cooldown_until', now + 30min)`, (b) send an alert email to `NARAROUTER_ALERT_EMAIL` (rate-limited to 1 per hour bucket via `Cache::add`), and (c) throw `AiAllProvidersUnavailable`. `SendAiResponse` catches the throw, reads `cooldown_until`, and `$this->release($secondsUntilCooldown + jitter)` — bounded by `$tries = 2`. Both cooldown-set + email-send + release semantics must fire. If you refactor, keep all three.

## 3. Current architecture (as of 2026-09-21)

### Env

Set in prod `.env` at `/var/www/ot1-pro.com/.env`, mode 600, owner `deploy:deploy`. Never `git`-tracked.

```
NARAROUTER_API_KEY=<primary — Nara account 1, Free tier>
NARAROUTER_API_KEY_SECONDARY=<secondary — Nara account 2, Free tier>
NARAROUTER_BASE_URL=https://router.bynara.id/v1
NARAROUTER_MODEL=nemotron-3-ultra-free
NARAROUTER_SCORING_MODEL=nemotron-3-ultra-free
NARAROUTER_TEXT_MODELS=nemotron-3-ultra-free,nemotron-3-super-free,nemotron-3.5-lightning-free,agnes-2.5-flash
NARAROUTER_VISION_MODELS=agnes-2.5-flash,nex-n2.5-pro,ling-3.0-flash-vl-free
NARAROUTER_RESET_HOURS=5
NARAROUTER_EXHAUSTION_COOLDOWN_MIN=30
NARAROUTER_ALERT_EMAIL=omareltak7@gmail.com
```

Legacy `NARAROUTER_FALLBACK_MODELS` is still read as a BC fallback for `TEXT_MODELS` when the new var is absent. Leave it unset in fresh installs.

### Model chain rationale

Nara's Free plan gives you access to a limited model set with `-free` suffix on non-paid variants. Non-`-free` models (`qwen3.8-max`, `deepseek-v4-flash`, `glm-*`, `kimi-*`, etc.) now return `429 rate_limited "Insufficient credits"` — they require a paid top-up.

**Text chain** (customer-visible quality first, safety net last):

| # | Model | Ctx | Vision | Why in this position |
|---|---|---|---|---|
| 1 | `nemotron-3-ultra-free` | 1M | ❌ | Weight 0.01x — nearly-free NVIDIA Nemotron. Primary because it's reliable and cheap. |
| 2 | `nemotron-3-super-free` | 262K | ❌ | Different Nemotron variant — outage isolation from Ultra. |
| 3 | `nemotron-3.5-lightning-free` | 1M | ❌ | Newer generation. Currently intermittent (was 502-ing 2026-09-21) but recovers. |
| 4 | `agnes-2.5-flash` | 512K | ✅ | Gemini Flash 2.5 — highest quality, works for text too. Final backup. |

**Vision chain**:

| # | Model | Ctx | Why |
|---|---|---|---|
| 1 | `agnes-2.5-flash` | 512K | Only reliably-up vision-capable model on Free tier. Primary. |
| 2 | `nex-n2.5-pro` | 262K | Free-tier vision. Currently 502-ing but should recover. |
| 3 | `ling-3.0-flash-vl-free` | 262K | Alibaba vision-language (`-vl-` suffix). Currently 502-ing but should recover. |

### Scoring model

`ScoreLeadJob` and `analyzeConversation` use `NARAROUTER_SCORING_MODEL = nemotron-3-ultra-free` (weight 0.01x). These are background jobs, never customer-visible. Using a cheap classifier here saves quota vs the reply chain. **Do not** upgrade this to a smart model "for better scoring" — the score doesn't drive customer-visible behavior directly.

### Two-key rotation (unchanged from v1)

Two API keys. Iteration order per model in a chain: `for k = 0..N-1: keyIdx = (cached_active_key + k) mod N`. Each chain caches its own `active_key_state` — text-chain key rotation and vision-chain key rotation are independent.

### Global cooldown

When both chains × both keys are exhausted within one `dispatch()`:
- `Cache::put('nararouter:cooldown_until', time() + 30min)` set BEFORE throwing
- Alert email sent (`Mail::raw` synchronous, rate-limited to 1/hour bucket)
- `AiAllProvidersUnavailable` thrown
- `SendAiResponse::handle()` catch block reads cooldown, `$this->release($secondsRemaining + random_int(10, 60))`
- `$tries = 2` bounds retries — one requeue max, then job moves to `failed_jobs`
- On first attempt only: `$team->markAiUpstreamPaused(15m)` fires for the customer-facing banner

## 4. Model catalog — what's actually usable on Free tier (as of 2026-09-21)

Full list at `https://router.bynara.id/models` when logged into a Nara account. Nara's pricing shifted in Q3 2026 — the `-free` suffix now has strict meaning. Verify via `/v1/models` and a curl probe before trusting historical assumptions.

### Confirmed working (probed 2026-09-21)

| Model | Chain | Verified | Notes |
|---|---|---|---|
| `agnes-2.5-flash` | both | 200 in 2-4s | Gemini Flash 2.5, best all-round free model |
| `nemotron-3-ultra-free` | text | 200 in 1.6s | Weight 0.01x — cheapest reliable text model |
| `nemotron-3-super-free` | text | 200 (intermittent) | Different Nemotron variant, worth including |

### Free-tier listed but currently 502-ing (upstream degraded — should recover)

| Model | Chain | Notes |
|---|---|---|
| `nemotron-3.5-lightning-free` | text | 1M ctx, newer gen |
| `nex-n2.5-pro` | vision | Free-tier vision |
| `ling-3.0-flash-vl-free` | vision | Alibaba vision-language |
| `ling-3.0-flash-fin-free` | — | Finance-tuned; not for customer chat |
| `ling-3.0-flash-sante-free` | — | Health-tuned; not for customer chat |
| `laguna-s-2.1` | — | Untested provenance, skip |

### Effectively unavailable on Free plan (returns 429 "Insufficient credits" or 402)

`qwen3.8-max`, `qwen3.8-flash`, `qwen3.8-27b`, `deepseek-v4-flash`, `deepseek-v4.1-flash`, `deepseek-v4-flash-vision-exp`, `glm-5.3`, `glm-5.3-flash`, `minimax-m3`, `mimo-v2.5-free` (despite the -free suffix), `stepfun-3.7-flash`, `kimi-k3`, `kimi-k2.7-code`, `agnes-3-flash`, `agnes-video-v2.0`, `muse-spark-1.3-contributor-free`. Do NOT add these to a chain — they'll 429 immediately and burn one round trip per attempt.

### Paid-only (not usable on Free plan without top-up)

`claude-sonnet-5`, `claude-opus-5`, `claude-opus-4.7`, `claude-opus-4.8`, `claude-fable-*`, `gpt-5.6-*`, `gpt-6-astra`, `grok-4.6`, `gemini-3.1-pro-high`, `gemini-3.8-flash-high`. Add these only after upgrading the Nara plan.

## 5. Diagnostic workflow when the AI is slow / stupid / silent

Follow in order. Do not skip a step.

### Step 1 — Confirm which chain + model is actually being used

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com && sudo -u www-data XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan tinker --execute="
use Illuminate\Support\Facades\Cache;
echo \"text  active_model: \".json_encode(Cache::get(\"nararouter:failover_state:text\")).\"\n\";
echo \"vision active_model: \".json_encode(Cache::get(\"nararouter:failover_state:vision\")).\"\n\";
echo \"text  active_key:   \".json_encode(Cache::get(\"nararouter:active_key_state:text\")).\"\n\";
echo \"vision active_key:   \".json_encode(Cache::get(\"nararouter:active_key_state:vision\")).\"\n\";
echo \"cooldown_until:      \".json_encode(Cache::get(\"nararouter:cooldown_until\")).\"\n\";
echo \"alert_bucket:        \".json_encode(Cache::get(\"nararouter:alert_sent:\".floor(time()/3600))).\"\n\";
echo \"config text chain:   \".config(\"services.nararouter.text_models\").\"\n\";
echo \"config vision chain: \".config(\"services.nararouter.vision_models\").\"\n\";
"'
```

If `active_model` for a chain is NOT the head of that chain and `reset_at` is in the future, we're stuck on a weaker model. The chain WILL return to head automatically at `reset_at`; if user can't wait, `Cache::forget('nararouter:failover_state:text')` (or `:vision`) forces immediate reset.

If `cooldown_until > time()` → both chains exhausted recently. All calls are short-circuiting until that timestamp.

### Step 2 — Are calls actually failing, or just slow?

```bash
ssh root@187.77.67.94 'grep -E "NaraRouter" /var/www/ot1-pro.com/storage/logs/laravel.log | tail -30'
```

Look for:
- `NaraRouter reply` info lines with `chain=text` / `chain=vision` / `fallback=false|true` — successful calls
- `NaraRouter API call failed` warnings → capture status and body. Match to §2 invariant D
- `NaraRouter primary chain exhausted, falling through to secondary` → cross-fallback fired
- `NaraRouter global cooldown set` → total exhaustion happened
- `NaraRouter both chains fully exhausted` (email subject) → check inbox
- Nothing → calls succeeding; if AI is slow but successful, jump to §5.5

### Step 3 — Probe both keys directly

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com
K1=$(grep ^NARAROUTER_API_KEY= .env | cut -d= -f2-)
K2=$(grep ^NARAROUTER_API_KEY_SECONDARY= .env | cut -d= -f2-)
for LABEL in PRIMARY SECONDARY; do
  if [ "$LABEL" = "PRIMARY" ]; then KEY=$K1; else KEY=$K2; fi
  echo -n "$LABEL: "
  curl -sS -o /dev/null -w "HTTP %{http_code}\n" -m 20 \
    -H "Authorization: Bearer $KEY" -H "Content-Type: application/json" \
    -d "{\"model\":\"nemotron-3-ultra-free\",\"messages\":[{\"role\":\"user\",\"content\":\"say ok\"}],\"max_tokens\":5}" \
    https://router.bynara.id/v1/chat/completions
done'
```

Any 401 → wrong key. Any 402 or 429 with body `"Insufficient credits"` → Free-plan quota exhausted for that account. Any 429 without that body → true rate-limit (usually clears within seconds). Any 502/523 → Nara upstream / Cloudflare degraded.

### Step 4 — Chain exhaustion → cooldown + alert should have fired

If you see `NaraRouter both chains fully exhausted` OR `NaraRouter global cooldown set`, check both:

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com && sudo -u www-data XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan tinker --execute="
use Illuminate\Support\Facades\Cache;
\$until = (int) Cache::get(\"nararouter:cooldown_until\", 0);
echo \"cooldown_until: \$until (\".date(\"Y-m-d H:i:s\", \$until).\")\n\";
echo \"remaining: \".max(0, \$until - time()).\"s\n\";
\$bucket = floor(time() / 3600);
echo \"alert lock this hour: \".json_encode(Cache::get(\"nararouter:alert_sent:\$bucket\")).\"\n\";
"'
```

If cooldown is set → jobs are already releasing with delay; nothing to do until the timestamp passes. If cooldown is NOT set but exhaustion was logged → provider code bug, investigate `dispatch()` in NaraRouterProvider.php.

To force-clear cooldown (e.g. Nara recovered early and a customer is waiting):

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com && sudo -u www-data XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan tinker --execute="
Illuminate\Support\Facades\Cache::forget(\"nararouter:cooldown_until\");
echo \"cleared\n\";
"'
```

### Step 5 — Latency without errors

If the call succeeds but takes forever:

```bash
ssh root@187.77.67.94 'journalctl -u one-inbox-queue@1.service -u one-inbox-queue@2.service -u one-inbox-queue@3.service -u one-inbox-queue@4.service --since "10 minutes ago" --no-pager | grep -E "SendAiResponse" | tail -30'
```

Typical numbers:
- `DONE 10-50ms` → gate-skip (paused, escalated, spam, no config). Not a latency problem.
- `DONE 5-15s` → head-of-chain healthy.
- `DONE 20-40s` → falling through the chain. Check §5.2 for which model succeeded.
- `DONE 40-90s` → primary chain exhausted, cross-fallback succeeded.
- `FAIL 4m` → hit job-level `$timeout = 240`. Very wrong — check for infinite loops in `dispatch()`.

## 6. Safe change procedures

**For adding or swapping models in either chain, use the dedicated `update-nararouter-models` skill.** It bundles the probe + verify + deploy sequence with model-alias verification against Nara's `/v1/models`.

### Rotating an API key

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com
  cp .env .env.bak.$(date +%s)
  # For primary:
  sed -i "s|^NARAROUTER_API_KEY=.*|NARAROUTER_API_KEY=<new-key>|" .env
  # OR for secondary:
  sed -i "s|^NARAROUTER_API_KEY_SECONDARY=.*|NARAROUTER_API_KEY_SECONDARY=<new-key>|" .env
  chown deploy:deploy .env && chmod 600 .env
  grep -c ^APP_KEY= .env
  sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan config:cache
  for N in 1 2 3 4; do systemctl restart one-inbox-queue@$N.service; done
'
# Then run the probe from §5.3 to confirm both keys are valid.
```

### Changing NARAROUTER_RESET_HOURS or NARAROUTER_EXHAUSTION_COOLDOWN_MIN

The reset hours value must be an integer ≥ 1. The provider uses `max(1, (int) config(...))`. Cooldown min must also be ≥ 1. If you change from 5 → e.g. 12 hours reset:
- Existing cached `reset_at` timestamps are NOT recomputed — they stay at their original `now + 5h`. Only NEW fallback events will use the new 12h window.
- If you want existing cached windows to reflect the new value immediately, `Cache::forget` all four per-chain state keys after `config:cache`.

### Force-recovery after an outage (skip the 30-min cooldown)

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com && sudo -u www-data XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan tinker --execute="
use Illuminate\Support\Facades\Cache;
Cache::forget(\"nararouter:cooldown_until\");
Cache::forget(\"nararouter:failover_state:text\");
Cache::forget(\"nararouter:failover_state:vision\");
Cache::forget(\"nararouter:active_key_state:text\");
Cache::forget(\"nararouter:active_key_state:vision\");
echo \"cleared all NaraRouter state\n\";
"'
```

Verify Nara is actually back first (probe from §5.3) or you'll just re-trigger the same outage.

## 7. Common failure modes and fixes

| Symptom | Likely cause | Fix |
|---|---|---|
| "AI is stupid — replies feel dumb" | Someone changed `NARAROUTER_MODEL` or `NARAROUTER_TEXT_MODELS` in prod .env | Check §5.1. Revert to canonical chain via `update-nararouter-models` skill. |
| "AI is stupid — but only sometimes" | Text chain cascaded to a lower-quality model, user sees mixed quality within the 5h window | Check `active_model:text`. If not head-of-chain, wait for `reset_at` or `Cache::forget('nararouter:failover_state:text')`. |
| "Image messages get weird replies" | Vision chain cascaded, or vision→text cross-fallback fired (model can't see the image) | Check `active_model:vision` + look for `NaraRouter primary chain exhausted, falling through to secondary` in logs. |
| "AI replies stopped" for entire team | Team-wide `markAiUpstreamPaused(15m)` fired | Wait 15 min OR clear `ai_upstream_paused_until` column. Root-cause the underlying outage first. |
| "AI replies stopped" cluster-wide + email received | Both chains exhausted, cooldown set | Wait for cooldown OR force-clear per §6 (only if Nara is actually back). |
| Queue backlog exploding during outage | Jobs failing to release-with-delay, or `$tries` too high | Check `SendAiResponse` catch block for `AiAllProvidersUnavailable` handling. `$tries` should be 2. |
| `NaraRouter 429` errors with body `"Insufficient credits"` | Free-tier quota exhausted on that account | Rotate accounts, wait for quota reset (Nara resets daily UTC-something), or top up the Free plan. |
| `HTTP 523` on all models | Nara's Cloudflare origin is down | Not our problem — wait for Nara to recover. Cooldown will auto-clear after 30 min; force-clear once probe returns 200. |
| Latency doubled, no errors | Chain cascaded to a slower model | Check `active_model:$kind`. Force reset with `Cache::forget`. |

## 8. What NOT to change without discussion

1. **Do not share cache keys between text and vision chains.** Suffix them.
2. **Do not lower `NARAROUTER_RESET_HOURS` below 1 or `NARAROUTER_EXHAUSTION_COOLDOWN_MIN` below 1.** Runaway resets or cooldowns defeat the purpose.
3. **Do not raise `NARAROUTER_EXHAUSTION_COOLDOWN_MIN` above 240 (4h).** The cooldown is server-load protection, not a manual switch — the auto-recovery on cooldown expiry is the whole point.
4. **Do not raise the HTTP `->timeout(25)`** back to 60 without a good reason. Fail-fast is deliberate.
5. **Do not add "smart" retry logic that overrides the model chain cascade.** The chain IS the retry.
6. **Do not remove `agnes-2.5-flash` from the tail of the text chain OR the head of the vision chain.** It's the most reliable free model — the "always works" backstop for both.
7. **Do not queue the exhaustion alert email.** The queue may be part of the outage. `Mail::raw()` is synchronous on purpose.
8. **Do not remove the `coalesceRoles()` call from `dispatch()`.** Anthropic's Messages API 400s on non-alternating roles.
9. **Do not modify `SendAiResponse::handle()`'s catch block** to skip the release-with-delay logic. The cooldown gate + release is what protects server load during a Nara outage.

## 9. Key files

- `app/Services/Ai/NaraRouterProvider.php` — main provider (~700 lines). Logic split between `dispatch()`, `runChain()`, `callChat()` (BC entry).
- `app/Jobs/SendAiResponse.php` — dispatch site; catches `AiAllProvidersUnavailable`, reads cooldown cache, releases job with delay.
- `config/services.php` — `nararouter` config block with `text_models`, `vision_models`, `exhaustion_cooldown_min`.
- `app/Services/Ai/BuildsConversationPrompts.php` — trait that builds system prompt + history.
- `tests/Unit/Services/Ai/NaraRouterCoalesceTest.php` — pins the coalesce invariant.
- `tests/Unit/Services/Ai/NaraRouterTwoChainTest.php` — pins two-chain + cooldown + cross-fallback invariants.
- Prod `.env` at `/var/www/ot1-pro.com/.env` — NARAROUTER_* keys (mode 600, deploy:deploy).
- Companion skill: `.claude/skills/nararouter-two-chain/` — architecture reference.
- Companion skill: `.claude/skills/update-nararouter-models/` — safe procedure for adding/swapping models.

## 10. When you finish a change

Same discipline as `ot1-pro-prod-ops`:

1. Note the commit SHA now on prod.
2. Run the §5.1 diagnostic — active models per chain match expectations.
3. Run the §5.3 probe — both keys return HTTP 200.
4. Watch `laravel.log` for 5 minutes — no unexpected `NaraRouter API call failed` warnings.
5. Send a real inbound → verify AI reply arrives in expected time (5-15s primary, 20-40s with cascade).
6. If touching vision chain: also send an inbound image → verify vision reply arrives + `chain=vision` shows in logs.
7. Append a line to `tasks/journal.md`.
