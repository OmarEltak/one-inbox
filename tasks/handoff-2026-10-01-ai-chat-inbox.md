# Handoff — AI chat / inbox / super-admin work (2026-10-01)

Branch `claude/laughing-goodall-65k8l0`. Deployed to prod via PRs #77–#81 (merge → GitHub Actions "Deploy to Production").
Container sessions cannot reach ot1-pro.com or SSH; ask the user for `/super-admin/errors` (ERROR level only) and `/super-admin/pages/{id}/diagnose`.

## Shipped (deployed)
| Area | What | Key files |
|---|---|---|
| Layout | Full-height pages (inbox, AI chat) sized `calc(100dvh - top bar)` (measured, 3.5rem fallback) | `layouts/app.blade.php` |
| AI chat UI | Opens at newest message; loader beside avatar (`wire:loading.flex`); Markdown replies (`.ai-md` in app.css); 6 marketing quick-questions; typed "send/ابعت" confirms pending action; full confirm text | `livewire/ai-chat.blade.php`, `AiChat.php` |
| AI chat brain | `AdminChatContext`: mentioned contacts (name/typo match, transcript, language, Meta reachability) + customer digest (all pages or the named page; falls back to `last_message_preview` for chats imported at connect time); no IDs in prose (prompt + `stripInternalIds`); history = last 12 turns starting on a user turn; per-page Meta 24h reach in context | `Services/Ai/AdminChatContext.php`, `BuildsConversationPrompts.php` |
| Bulk send honesty | `resolveBulkTargets` feeds confirm bar + executor; Messenger/IG contacts outside 24h excluded and reported | `AiChat.php` |
| Customer AI | `[image]`/`[voice note]` never sent as a reply; history narrates media | `Services/Ai/MediaPlaceholders.php`, `SendAiResponse.php` |
| NaraRouter | 200-with-empty-content now tries the next model; all-empty → `Log::error` + '' with **no** global cooldown; admin chat max_tokens 4000 | `NaraRouterProvider.php` (+2 tests in `NaraRouterTwoChainTest`) |
| Inbox | Composer no longer steals focus; emoji picker inserts into textarea; document chip = inset card | `inbox/index.blade.php`, `resources/js/app.js`, `components/inbox/media-bubble.blade.php` |
| Connectors tab | AI Settings → Connectors: Google Sheets (Apps Script web app), webhook, Excel CSV export; row on Sales-Goal completion or contact → Converted. Migration `ai_configs.sales_connectors` | `Services/SalesConnectors/*`, `Jobs/PushSalesConnectorRow.php` |
| Capture fields | Single "Field" input (label derived: `AiConfig::captureFieldLabel`) | `settings/ai-config.blade.php` |
| Super-admin | Customers: AI usage, pages + connection type, sign-up date + sort, mobile. Subscriptions: "Reset campaigns" (settings.campaign_quota_reset_at), Revoke tooltip, flash shown as corner toast | `SuperAdmin/Customers.php`, `Subscriptions.php`, `Team::campaignsCreatedThisMonth` |
| FB import | Connect-time Messenger import truncates previews to 250 chars, skips a bad row instead of aborting, never reopens archived chats; one-off migration re-syncs all active Messenger pages on deploy | `FacebookPlatform::fetchConversations`, migration `2026_10_01_000002_resync_facebook_conversations.php` |
| i18n | 200+ Arabic entries; `tests/Unit/ArabicTranslationCoverageTest.php`; CLAUDE.md pin #12 | `lang/ar.json` |

## Verify after the last deploy (#81)
1. Mishkah inbox: within minutes the conversation count should grow from 28 to the real number (re-sync migration). If not: `/super-admin/errors` → look for `Failed to fetch conversations` (token/permission) or "Skipped a conversation during sync" (warning level — not on that page).
2. `/ai-chat`: "What are last 30 customers for mishkah want" → themes with quotes from previews. If "Sorry, I encountered an error" → `/super-admin/errors` now logs `AI chat request failed: <message>` with file:line. If "API error" → look for `NaraRouter: no model produced a reply (empty content)` (attempts list shows finish_reason per model).
3. Subscriptions: Reset/Revoke shows a green toast bottom-corner.
4. Inbox document chip: darker inset card, readable on hover.

## Still open / not done
- **Full Facebook history is not imported** — only one-line previews per chat; messages arriving after connect are stored in full. A backfill (last ~20 messages per conversation via `FacebookPlatform::fetchMessages`, must NOT dispatch AI replies, paced for Meta rate limits) was offered, not built.
- **Root cause of "Sorry, I encountered an error" (exception inside `chatWithAdmin`) is unconfirmed** — logging added; read the next occurrence.
- `Analytics.php:321` query (`SELECT sender_type, …`) hits MySQL max_execution_time repeatedly — pre-existing, not touched.
- Page "Omar Eltak" conversation fetch fails with OAuthException 190 (token lacks page permissions) — reconnect that page.
- Deploy race: one `database.sqlite does not exist` error during `config:clear` window at deploy time — pre-existing pipeline issue.
- UI fixes were not click-tested in a real browser from the container (prod unreachable); user verified 1/3/5 visually.
- Pre-existing failing tests on main: `DashboardTest`, `Campaigns/TestSendThrottleTest`; `SendAiResponseSpamGuardTest` hangs (typing-delay sleep).
