---
name: nararouter-two-chain
description: Use whenever you need to understand how the NaraRouter provider actually behaves — before editing NaraRouterProvider.php, adding a new AI call site, debugging why a message went through vision vs text, tracing where the 30-minute global cooldown came from, or reasoning about cross-chain fallback. This is the architecture reference; for the operational runbook (env vars, diagnostic commands, key rotation, common failures) use `nararouter-ops`. For the safe procedure to add or swap models in either chain, use `update-nararouter-models`.
---

# NaraRouter Two-Chain Architecture

## The one-picture summary

```
                       incoming Message
                              │
                              ▼
                   ┌─────────────────────┐
                   │ detectMessageKind() │  content_type != 'text' OR media_url/type set?
                   └─────────┬───────────┘
                             │
                       vision / text
                             │
                             ▼
              ┌──────────────────────────────┐
              │   dispatch($kind, ...)       │
              └──────────────┬───────────────┘
                             │
              ┌──────────────▼────────────┐
              │ nararouter:cooldown_until │─── set + in future?  ── throw AiAllProvidersUnavailable
              └──────────────┬────────────┘
                             │  (no cooldown)
                             ▼
                    runChain($kind)  ────── ✓ → return reply
                             │
                        exhausted
                             │
                             ▼
                 runChain(other kind)  ──── ✓ → return reply (degraded — vision→text drops the image)
                             │
                        exhausted
                             │
                             ▼
        setCooldown(now + 30min) + sendExhaustionAlert() + throw
                             │
                             ▼
        SendAiResponse catches:  $this->release( secondsUntilCooldown + jitter )
                                (bounded by $tries = 2 → auto-fail after one requeue)
```

## The two chains

Two independent, ordered lists of NaraRouter model aliases. Each chain has its own reset window, key rotation, and cached active-model pointer.

**Text chain** — used when the incoming message is plain text:
```
NARAROUTER_TEXT_MODELS=nemotron-3-ultra-free,nemotron-3-super-free,nemotron-3.5-lightning-free,agnes-2.5-flash
```

**Vision chain** — used when the incoming message has an attachment (image / sticker / video / audio / document — anything where `content_type != 'text'` OR `media_url`/`media_type` is set):
```
NARAROUTER_VISION_MODELS=agnes-2.5-flash,nex-n2.5-pro,ling-3.0-flash-vl-free
```

Why two chains: quality and cost. Vision-capable models are scarcer and slower; using them for text-only requests would waste quota. Text-only models are cheaper and often faster; using them for image requests would drop the image entirely.

## Message-kind detection

`NaraRouterProvider::detectMessageKind(Message $m): string` — returns `'text'` or `'vision'`. Rule (mirrors `SendAiResponse::isMediaMessage()`):

- `content_type != 'text'` AND `content_type != ''` → **vision** (image/video/audio/sticker/document/file)
- `media_url` OR `media_type` set → **vision**
- otherwise → **text**

Only `generateResponse()` (the customer-reply path) inspects the message. Every other caller (`scoreMessage`, `analyzeConversation`, `generateText`, `chatWithAdmin`) always runs the **text chain** because they have no incoming Message to inspect — the fallback to vision on text exhaustion still fires for them, so a total text outage doesn't kill background scoring.

## Cross-chain fallback

If the primary chain (whichever kind was chosen) fully exhausts every model × every key, `dispatch()` runs the OTHER chain in degraded mode.

- **text→vision fallback:** vision models can handle text fine. No prompt modification. Just costs more quota.
- **vision→text fallback:** the text model literally cannot see the image. `dispatch()` appends a note to the system prompt: *"The customer sent an image, but the current AI model cannot process images. If they reference an image, politely ask them to describe it in text or resend."* This is a legitimate degraded reply — better than silent failure.

The log line `NaraRouter primary chain exhausted, falling through to secondary` marks a cross-fallback event.

## Per-chain cache keys (do NOT share)

Five cache keys total. The four per-chain keys must remain suffix-namespaced (`:text` / `:vision`), or text success poisons vision's start pointer and vice versa. The unit test `NaraRouterTwoChainTest::test('vision primary success caches vision active_model only, does not touch text')` pins this.

| Key | Contents | Purpose |
|---|---|---|
| `nararouter:failover_state:text` | `['model' => 'nemotron-3-super-free', 'reset_at' => 1730000000]` | Text chain last-successful model + reset window end |
| `nararouter:failover_state:vision` | `['model' => 'agnes-2.5-flash', 'reset_at' => ...]` | Vision chain last-successful model + reset window end |
| `nararouter:active_key_state:text` | `['index' => 1, 'reset_at' => ...]` | Text chain last-successful key index (0=primary, 1=secondary) |
| `nararouter:active_key_state:vision` | `['index' => 0, 'reset_at' => ...]` | Vision chain last-successful key index |
| `nararouter:cooldown_until` | Unix timestamp | Global cooldown — when set + in future, `dispatch()` short-circuits |
| `nararouter:alert_sent:{hourBucket}` | `1` | Rate-limit lock for exhaustion alert email (1 per hour bucket) |

## The 5h reset window (per chain)

Load-bearing invariant. `markActiveModel($kind, $model)` and `markActiveKey($kind, $index)` PRESERVE the existing `reset_at` value on every subsequent successful call. Only the FIRST fallback event (or a call after the previous window expired) opens a fresh 5h window.

Effect: each chain returns to its head-of-chain at most 5 hours after we first fell back on that chain, regardless of how many successful calls landed in between. If you "optimize" this by refreshing `reset_at` on every 200, the chain never returns to head — you're stuck on the fallback model forever.

The two chains' reset windows are independent. Text can be on `nemotron-3-super-free` (fallback) while vision is on `agnes-2.5-flash` (head-of-chain).

## The 30-minute global cooldown

The single most important addition in v2. Purpose: **protect server resources during a sustained Nara outage.**

Without cooldown, every queued message during an outage triggers 15-30s of pointless curl cascade (all models 502, cross-chain 502, throw). With 4 queue workers × dozens of messages/minute, that's hundreds of pointless outbound HTTP requests during a 30-min outage.

With cooldown, the very first `dispatch()` call that exhausts everything sets `nararouter:cooldown_until = time() + 30min`. Every subsequent call within that window short-circuits at the top of `dispatch()` — µs cache check, throws `AiAllProvidersUnavailable` immediately, zero HTTP.

`SendAiResponse::handle()` catches the throw and:
1. Reads `nararouter:cooldown_until`
2. Computes `$delay = ($cooldownUntil - time()) + random_int(10, 60)` (jitter prevents thundering herd)
3. `$this->release($delay)` — puts the job back on the queue with delay
4. Job's `$tries = 2` bounds retries: at most one release-with-delay per job, then Laravel moves it to `failed_jobs`
5. On first attempt only, also `$team->markAiUpstreamPaused(15m)` — customer-facing banner

When a released job wakes up 30 minutes later, the existing "human replied since trigger?" gate at `SendAiResponse::handle()` line 130 naturally skips the reply if a moderator already took over during the wait. No manual cancellation bookkeeping needed.

## Key rotation (unchanged from v1)

Two API keys: `NARAROUTER_API_KEY` (primary, Nara account 1) and `NARAROUTER_API_KEY_SECONDARY` (optional, Nara account 2). Each key = one Nara account with its own Free-tier quota.

Iteration order per model in a chain:
```
for k = 0..N-1:
    keyIdx = (cached_active_key_for_this_chain + k) mod N
    try (model, keyIdx)
```

Key rotation caching is per-chain. Text chain and vision chain can be on different active keys simultaneously (rare, but possible if one Nara account exhausts quota on text models but has quota left for vision).

## Status-code → action mapping (unchanged from v1)

Deliberate design — do not change without reading `nararouter-ops` §2 invariant D.

| Status | Action |
|---|---|
| 200 | mark active (for this chain), return reply |
| 400 | payload bug — return `''` immediately, no retry (retrying makes it worse) |
| 401 / 402 / 403 / 429 | try next KEY, same model |
| 404 / 5xx / timeout | cascade to next MODEL, restart key rotation |

Rationale:
- **429 → next key**: the other Nara account has its own quota bucket. If it's also 429 with body `"Insufficient credits"`, then both accounts are drained and we cascade to the next model (which may have a different quota class).
- **5xx → next model**: same key won't fix a 502. Different model may be on a different Nara upstream.
- **400 → give up**: it's a payload bug (bad JSON, invalid parameters, coalesce failure). No key or model can save it — the fix is in our code.

## Coalesce invariant (unchanged from v1)

Anthropic's Messages API (which NaraRouter proxies) requires strict user/assistant alternation. `NaraRouterProvider::coalesceRoles()` merges consecutive same-role turns with `\n\n`, drops empty content, and normalizes the legacy `model` role (Gemini heritage) to `assistant`. Called by `dispatch()` before every payload build — covers all five caller paths (`generateResponse`, `scoreMessage`, `generateText`, `analyzeConversation`, `chatWithAdmin`).

Pinned by `tests/Unit/Services/Ai/NaraRouterCoalesceTest.php` (8 tests) — must stay green.

## Method map (as of 2026-09-21)

| Method | Access | Purpose |
|---|---|---|
| `generateResponse(Conversation, Message, AiConfig)` | public | Customer-reply entry. Detects kind, calls `dispatch()`. |
| `scoreMessage(Message, Contact)` | public | Background scoring. Uses `callChat()` with scoring model. |
| `analyzeConversation(Conversation)` | public | Background analysis. Uses `callChat()` with scoring model. |
| `generateText(string, string)` | public | Generic text call (used by CaptureExtractor). Uses `callChat()`. |
| `chatWithAdmin(...)` | public | Admin Command Center chat. Uses `callChat()` with error strings. |
| `detectMessageKind(Message)` | public | Returns `'text'` or `'vision'`. Public for test/reuse. |
| `coalesceRoles(array)` | public | Merges same-role turns. Public for tests. |
| `callChat($modelHint, ...)` | protected | BC entry point — routes to `dispatch(text)` or `directCall()`. |
| `dispatch($kind, ...)` | protected | Orchestrator — cooldown gate + primary chain + cross-fallback + exhaustion. |
| `runChain($kind, ...)` | protected | Single-chain iterator. Returns status envelope. |
| `directCall($model, ...)` | protected | One-shot, no cascade. Used only when `$modelHint` is outside both chains. |
| `currentModel($kind)` | protected | Cached active model for the chain, or head-of-chain. |
| `markActiveModel($kind, $model)` | protected | Cache write preserving `reset_at`. |
| `currentKeyIndex($kind)` | protected | Cached active key for the chain, or 0. |
| `markActiveKey($kind, $index)` | protected | Cache write preserving `reset_at`. |
| `isInCooldown()` | protected | µs cache check. |
| `cooldownRemainingSec()` | protected | For SendAiResponse's release delay computation. |
| `setCooldown()` | protected | Set on total exhaustion, before throwing. |
| `sendExhaustionAlert($attemptsPerChain, $lastError)` | protected | Rate-limited email. |

## Do NOT

- Share cache keys between chains (drop the `:text` / `:vision` suffix).
- Refresh `reset_at` on every success in `markActiveModel` / `markActiveKey`.
- Add retry-with-backoff on 5xx (the chain IS the retry).
- Remove the cooldown gate at the top of `dispatch()` (server-load protection).
- Remove the `$this->release()` in `SendAiResponse` catch (jobs would either retry immediately during outage OR silently fail forever).
- Skip `coalesceRoles()` — Anthropic 400s on non-alternating.
- Modify `runChain()` to loop back to the head of its chain — the 5h reset is the intentional return-to-head mechanism.
- Queue the exhaustion alert email — the queue may be part of the outage.

## Related skills

- `nararouter-ops` — operational runbook (env, diagnostics, key rotation, failure modes)
- `update-nararouter-models` — safe procedure for changing chain composition
- `ot1-pro-prod-ops` — general prod-touching discipline
