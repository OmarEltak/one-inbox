---
name: update-nararouter-models
description: Use when the user says "add these models to nararouter", "update the text chain", "update the vision chain", "add [model-name] to text/vision", "swap [model] for [other-model] in nararouter", "reorder the nararouter chains", or when the user pastes a list of NaraRouter model IDs (with or without pricing/weight info) and asks to add or swap them into our chains. Also use when the user says the current chain is missing a specific model, is stuck on a lower-quality model, or when Nara's model catalog has changed and we need to resync. Follows the safe procedure that verifies model aliases against Nara's /v1/models before shipping so we don't ship a phantom chain that 404s on every attempt (which is what happened on 2026-09-21 — 4 of 6 chain models were silently invalid).
---

# Update NaraRouter Models — Safe Change Procedure

Use this skill any time you're about to change `NARAROUTER_TEXT_MODELS` or `NARAROUTER_VISION_MODELS` on prod, whether it's adding a single model, reordering, or full replacement. The steps prevent the "phantom chain" failure mode where names diverge from Nara's actual aliases and every attempt 404s.

**Prerequisites:** load `nararouter-ops` first if you haven't already — it contains the diagnostic commands and cache-key names this skill references. Load `nararouter-two-chain` if you're not already familiar with the two-chain design.

## The 5-step procedure

### Step 1: Understand the ask

Parse what the user actually wants. Common cases:

- **"Add model X to text chain"** → append or insert X into `NARAROUTER_TEXT_MODELS`
- **"Add model X to vision chain"** → append or insert X into `NARAROUTER_VISION_MODELS`
- **"Add these models"** (with a pasted list) → decide per model whether it goes to text, vision, or both, using the routing rules in Step 2
- **"Update the chain"** (vague) → ask which chain, or default to text (more common)
- **"Reorder"** → confirm the new order before doing anything

If the user pasted a Nara pricing table with weights, the model is Free-tier accessible ONLY if the price/weight columns show `—` or `0` OR the name ends with `-free`. Everything else is paid and will 429 with "Insufficient credits" on our Free plan.

### Step 2: Route to text vs vision

Rules (check the model's capability at `https://router.bynara.id/models`):

| Model property | Route to |
|---|---|
| Vision-capable (has `vision: true` in `/v1/models` response, or Nara UI shows a `Vision` badge) | Vision chain (and optionally text chain as backup) |
| Text-only | Text chain only |
| Video / audio / speech-only | Neither — not usable by our chat provider |
| Domain-tuned (finance = `-fin-`, health = `-sante-`, code = `-code-`) | Skip — worse than general models for sales chat |
| `-vl-` suffix | Vision chain (vision-language variant) |
| Untested provenance (`laguna-*`, `muse-*`, obscure names) | Skip unless the user explicitly asks and takes the risk |

If unsure, ask the user which chain — do NOT guess. A model in the wrong chain either wastes vision-quota on text calls or fails to see images.

### Step 3: Verify the aliases exist (mandatory)

Query Nara's `/v1/models` and confirm every proposed alias appears verbatim. Nara silently renames models — the runbook's chain caught 4 phantom aliases (`agnes-2.0-flash`, `nemotron-3-ultra` → `-free`, `qwen-3.8-max-free` → `qwen3.8-max`, `mistral-large` deleted entirely). Skip this step at your peril.

```bash
ssh root@187.77.67.94 "cd /var/www/ot1-pro.com && K=\$(grep ^NARAROUTER_API_KEY= .env | cut -d= -f2-) && curl -sS -H \"Authorization: Bearer \$K\" https://router.bynara.id/v1/models -o /tmp/nara.json && python3 -c 'import json; d=json.load(open(\"/tmp/nara.json\")); [print(m[\"id\"]) for m in sorted(d[\"data\"], key=lambda x: x[\"id\"])]'"
```

For each proposed new model, `grep -qxF "$model" /tmp/nara.json` (or eyeball it). If it's not in the list, do NOT add it — the alias is wrong or the model was removed.

### Step 4: Probe each proposed model (mandatory)

Even if the alias exists, the model may return 402 (paid tier), 429 (Free-plan quota locked), or 502 (upstream degraded). Probe against BOTH keys with a small delay to avoid hitting per-minute rate limits:

```bash
ssh root@187.77.67.94 "cd /var/www/ot1-pro.com
K=\$(grep ^NARAROUTER_API_KEY= .env | cut -d= -f2-)
for M in <space-separated-list-of-proposed-models>; do
  R=\$(curl -sS -o /tmp/r -w '%{http_code} %{time_total}s' -m 25 \
    -H \"Authorization: Bearer \$K\" -H 'Content-Type: application/json' \
    -d \"{\\\"model\\\":\\\"\$M\\\",\\\"messages\\\":[{\\\"role\\\":\\\"user\\\",\\\"content\\\":\\\"reply OK\\\"}],\\\"max_tokens\\\":10}\" \
    https://router.bynara.id/v1/chat/completions)
  BODY=\$(head -c 200 /tmp/r)
  printf '%-40s %s | %s\n' \"\$M\" \"\$R\" \"\$BODY\"
  sleep 5
done"
```

Interpret the results:

| Result | Interpretation | Action |
|---|---|---|
| `200` | Works right now | ✅ Include in chain |
| `402` | Paid-only, our Free plan can't access | ❌ Skip — will 402 every call |
| `429 "Insufficient credits"` | Free-tier quota drained OR paid-tier locked | ❌ Skip — same as 402 in practice |
| `429` without that body | True rate-limit, transient | ⚠️ Probe again later with 10s delay |
| `403 "Your plan does not include"` | Paid-only | ❌ Skip |
| `502` / `523` | Upstream degraded | ⚠️ Include ONLY if user accepts intermittent — cascade past when 502 is <1s cost |
| `404` | Alias wrong (shouldn't happen if Step 3 was clean) | ❌ Skip |

A model that 502s today but is listed as free on Nara's price table is usually safe to include — when it recovers it adds real fallback capacity, and when it's down we cascade past quickly.

### Step 5: Show the diff, get GO, deploy

Show the user the exact `.env` lines that will change, e.g.:

```diff
- NARAROUTER_TEXT_MODELS=nemotron-3-ultra-free,agnes-2.5-flash
+ NARAROUTER_TEXT_MODELS=nemotron-3-ultra-free,nemotron-3-super-free,nemotron-3.5-lightning-free,agnes-2.5-flash
```

**Wait for explicit GO** (prod .env change per CLAUDE.md + `ot1-pro-prod-ops` skill discipline). Even in auto mode this needs approval.

On GO:

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com
  # 1. Backup .env FIRST (timestamped)
  cp .env .env.bak.$(date +%s)

  # 2. Update the chain(s) — use sed on the specific line(s). Example for text chain:
  sed -i "s|^NARAROUTER_TEXT_MODELS=.*|NARAROUTER_TEXT_MODELS=<new-comma-list>|" .env
  # Or if adding vision:
  # sed -i "s|^NARAROUTER_VISION_MODELS=.*|NARAROUTER_VISION_MODELS=<new-comma-list>|" .env

  # 3. Fix ownership (config:cache will fail if wrong)
  chown deploy:deploy .env && chmod 600 .env

  # 4. Sanity check APP_KEY still there (defensive — this line has bit us on 500s before)
  test $(grep -c ^APP_KEY= .env) -eq 1 || { echo "ABORT: APP_KEY missing"; exit 1; }

  # 5. Rebuild config cache AS deploy user (root user causes MissingAppKeyException 500s per feedback memory)
  sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan config:cache
  systemctl reload php8.4-fpm

  # 6. Restart the 4 queue workers so they pick up the new chain
  for N in 1 2 3 4; do systemctl restart one-inbox-queue@$N.service; done

  # 7. Force-clear the per-chain cache so the new head-of-chain is tried FIRST
  #    (skip if the primary head is unchanged — but always safe to run)
  sudo -u www-data XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan tinker --execute="
    Illuminate\Support\Facades\Cache::forget(\"nararouter:failover_state:text\");
    Illuminate\Support\Facades\Cache::forget(\"nararouter:failover_state:vision\");
    Illuminate\Support\Facades\Cache::forget(\"nararouter:active_key_state:text\");
    Illuminate\Support\Facades\Cache::forget(\"nararouter:active_key_state:vision\");
  "
'
```

Then verify per `nararouter-ops` §10:
1. Run `nararouter-ops` §5.1 diagnostic — confirm active_model is the new head of chain (or empty).
2. Run `nararouter-ops` §5.3 probe — both keys return 200 on the new primary.
3. Watch `laravel.log` for 5 minutes — no unexpected `NaraRouter API call failed` warnings.
4. Send a real inbound message (text OR image, depending on which chain you touched) → verify AI reply arrives + log shows `chain=<expected>` and `model=<expected head>`.

## Rollback

Simple — every deploy above wrote a timestamped `.env.bak.*`:

```bash
ssh root@187.77.67.94 'cd /var/www/ot1-pro.com
  BACKUP=$(ls -t .env.bak.* | head -1)
  cp "$BACKUP" .env
  chown deploy:deploy .env && chmod 600 .env
  sudo -u deploy XDG_CONFIG_HOME=/tmp HOME=/tmp php artisan config:cache
  systemctl reload php8.4-fpm
  for N in 1 2 3 4; do systemctl restart one-inbox-queue@$N.service; done
'
```

## What the skill does NOT do

- **Does not modify `NARAROUTER_MODEL`** (the "hint" primary). That's a separate operation — usually you want to change the chain FIRST and only change `NARAROUTER_MODEL` if you want a callable outside the chain (rare). If the user wants to change the primary, ask if they mean "make X the head of text chain" (this skill) or "pin X as a direct-call model" (different flow).
- **Does not modify `NARAROUTER_SCORING_MODEL`.** Scoring is background and should stay cheap (`nemotron-3-ultra-free` weight 0.01x). Change only on user request.
- **Does not modify keys, base_url, reset_hours, cooldown_min, or alert_email.** Those are in `nararouter-ops` §6.
- **Does not restart Reverb, scheduler, or nginx.** Only the queue workers need the new config.

## Common gotchas

- **Silent Nara renames.** `nemotron-3-ultra` became `nemotron-3-ultra-free` sometime in 2026. The old alias 404s. Always verify against `/v1/models` — do not trust historical assumptions or memory from prior sessions.
- **`-free` suffix is not cosmetic.** As of Q3 2026, non-`-free` variants (`qwen3.8-max`, `deepseek-v4-flash`, `glm-*`) require paid Nara credits. Free plan → 429 "Insufficient credits."
- **Vision models without `vision: true`** in `/v1/models` won't actually process images even if their name sounds like vision. `-vl-` in the name is the reliable signal (Alibaba convention).
- **A chain of one model is legal** but has no fallback. If the sole model 5xx's, we cross-fall to the other chain. Prefer at least 2 models per chain when possible.
- **A chain can be empty** — the provider treats an empty chain as immediately exhausted for that kind. Never intentionally empty a chain; use the other chain as sole option or add a placeholder.

## Related skills

- `nararouter-ops` — operational runbook (diagnostics, key rotation, failure modes, "when things break")
- `nararouter-two-chain` — architecture reference (why two chains, per-chain cache keys, cross-fallback, cooldown)
- `ot1-pro-prod-ops` — general prod-touching discipline
- `evidence-first-diagnosis` — invoke this BEFORE assuming a model works; the probe step in this skill is a specific instance of that skill's discipline
