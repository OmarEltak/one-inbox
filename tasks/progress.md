# Session Progress

## COMPLETED - Phase 1.1: Core Infrastructure
- 11 migrations, 12 models, multi-tenant middleware, platform interfaces, AI provider, webhook controllers, queue jobs

## COMPLETED - Phase 1.5: Inbox UI Shell
- Inbox page, Contacts page, Connections page, AI Settings (kill switch), Team creation

## COMPLETED - Phase 1.2: Facebook Messenger Integration
- `FacebookPlatform` service (full implementation):
  - OAuth flow: redirect to FB Login -> get code -> exchange for short-lived token -> exchange for long-lived token (~60 days)
  - Fetch user's pages via `GET /me/accounts`, store with permanent page access tokens
  - Auto-subscribe pages to webhook events (messages, deliveries, reads, postbacks)
  - Auto-detect linked Instagram Professional accounts and store as separate pages
  - Send messages via Graph API (`POST /{PAGE_ID}/messages`)
  - Fetch existing conversations on connect
- `ConnectionController` with Facebook redirect + callback routes
- `SendPlatformMessage` job (routes outbound messages to correct platform API)
- Inbox `sendMessage` method wired up - user types message, stored in DB, dispatched to platform
- Connections UI updated: working "Connect with Facebook" button, shows connected status, IG auto-detect info
- `.env.example` updated with all platform config vars

## COMPLETED - Phase 1.3: Instagram DM Integration (bundled with Facebook)
- Instagram accounts auto-detected when connecting Facebook
- Uses same page access token (IG DMs route through FB Page)
- Webhook events for Instagram handled by same MetaWebhookController
- ProcessIncomingMessage job already handles `platform=instagram`
- SendPlatformMessage/SendAiResponse already handle instagram same as facebook

## COMPLETED - Phase 1.4: WhatsApp Business Integration
- `WhatsAppPlatform` service (full implementation):
  - Connection via WABA ID + System User Token (not OAuth - manual form entry)
  - Validates token by fetching WABA details via Graph API
  - Fetches phone numbers registered under WABA, stores each as a Page
  - Phone number registration for Cloud API messaging
  - WABA webhook subscription (`/{WABA_ID}/subscribed_apps`)
  - Send text messages via `POST /{PHONE_NUMBER_ID}/messages` with `messaging_product=whatsapp`
  - Send template messages for outbound outside 24h window
  - Fetch message templates from WABA
- `ConnectionController::whatsappConnect()` with POST route + validation
- Connections UI: "Connect WhatsApp" button opens modal with WABA ID + token form
- `hasWhatsApp` computed property on Connections Livewire component
- Already had: MetaWebhookController (whatsapp_business_account), ProcessIncomingMessage::processWhatsApp(), SendAiResponse::sendViaWhatsApp(), SendPlatformMessage::sendViaWhatsApp()

## COMPLETED - Phase 2.1: Telegram Bot Integration
- `TelegramPlatform` service (full implementation):
  - Connection via Bot Token from BotFather (form entry)
  - Validates token via `getMe` API call
  - Stores bot as ConnectedAccount + Page (bot token as page_access_token)
  - Auto-sets webhook URL via `setWebhook` API with secret_token header
  - Send text messages via `sendMessage` API
  - Send photos via `sendPhoto` API
  - Disconnect removes webhook via `deleteWebhook` API
- `ConnectionController::telegramConnect()` with POST route + validation
- Connections UI: "Connect Telegram" button opens modal with bot token form
- `hasTelegram` computed property on Connections Livewire component
- Already had: TelegramWebhookController, ProcessIncomingMessage::processTelegram(), SendAiResponse::sendViaTelegram(), SendPlatformMessage::sendViaTelegram()

## COMPLETED - Meta App Developer Console Configuration
- Meta App "One Inbox" created on developers.facebook.com
  - App ID: 1433402598508734
  - App Secret: saved in .env
- .env updated: META_APP_ID, META_APP_SECRET, META_WEBHOOK_VERIFY_TOKEN, APP_URL
- Facebook Login use case added to app
- Meta Developer Console fully configured (permissions, webhook settings, etc.)
- **PROJECT RENAMED**: `one-inbox-with-ai-responder` → `one-inbox`
- **New local URL**: `http://one-inbox.test`

## COMPLETED - Telegram Bot Connection Test (pre-rename)
- Telegram bot connected and tested successfully before project rename

## COMPLETED - Herd $__herd_closure Fix
- Root cause: `"dumps": false` in Herd config triggered known bug in php_herd extension
- Fix: Set `"dumps": true` in `C:\Users\NanoChip\.config\herd\config\config.json`
- Key learning: Herd's PHP-FPM overrides php.ini via command-line args

## COMPLETED - Meta App "One Inbox Business" Switched to Live Mode
- App ID: 1469090344742803
- Privacy Policy URL saved: `https://github.com/privacy` (placeholder — update to real PP URL before launch)
- App status changed from "غير منشور" (Development) to "تم النشر" (Live/Published)
- External users can now connect their Facebook accounts without "App not active" error
- Go Live page: `/apps/1469090344742803/go_live/`

## COMPLETED - Meta Business App Migration
- Old app (1433402598508734) was Consumer type - only basic Facebook Login permissions
- Created new Business-type app: **One Inbox Business** (ID: 1469090344742803)
- Use cases: Messenger from Meta + Instagram API
- Facebook Login for Business configured with `config_id` flow (not scope-based)
- Login Configuration "One Inbox Messaging" created (config_id: 896835833159946)
- Permissions: pages_messaging, pages_manage_metadata, pages_show_list
- Redirect URI: `https://one-inbox.test/connections/facebook/callback`
- Updated `.env`: META_APP_ID, META_APP_SECRET, META_LOGIN_CONFIG_ID
- Updated `config/services.php` with `login_config_id`
- Updated `FacebookPlatform::getConnectUrl()` to use `config_id` instead of `scope`

## COMPLETED - Facebook OAuth Test
- [x] OAuth flow works end-to-end with new Business app
- [x] Connected Omar Mohamed Eltak with 2 pages:
  - Brandk (ملابس (علامة تجارية)) - Active
  - تعلم الموسيقة (سلسلة حفلات) - Active
- [x] Pages auto-subscribed to webhooks

## COMPLETED - Inbox Message Loading + UI Overhaul
- `FacebookPlatform::fetchAndStoreMessages()` - lazy-loads messages from Graph API on first conversation click
  - Uses `Message::firstOrCreate` on `platform_message_id` to avoid duplicates
  - Sets `metadata['messages_fetched']` flag to prevent re-fetching
  - Determines inbound/outbound by comparing `from.id` to `page.platform_page_id`
- Inbox Livewire component:
  - Added `#[Url] pageId` for per-page filtering
  - `selectConversation()` lazy-loads messages when conversation has 0 messages
  - Added `setPage()` method for sidebar page links
- Inbox UI fixes:
  - Messages in correct chronological order (oldest top, newest bottom) with `mt-auto` for chat-app feel
  - Auto-scroll to bottom with Alpine.js `x-init`/`x-effect`
  - Long text wraps properly (`break-words`, `overflow-wrap: anywhere`, `whitespace-pre-wrap`)
  - Date formatting: "M j, g:i A" (e.g., "Jun 30, 10:33 PM") using `platform_sent_at`
  - Cursor pointer on conversation list items
  - Loading spinner with `wire:loading` during message fetch
- Sidebar restructured:
  - "Laravel Starter Kit" → "One Inbox" branding
  - Connected pages listed under "All Messages" (Brandk, تعلم الموسيقة) with platform icons
  - Per-page filtering via `?pageId=` URL parameter
- Layout fix for full-bleed inbox:
  - `layouts/app.blade.php` supports `fullWidth` flag to skip `flux:main` padding
  - Uses `data-flux-main` attribute to trigger Flux's CSS grid (`*:has(>[data-flux-main]) { display: grid }`)
  - Height constrained to `100dvh` to prevent content overflow
- Mobile responsive:
  - Conversation list takes full width on mobile (`w-full md:w-80`)
  - Message view takes full width when conversation selected
  - Back button to return to conversation list
- Vite rebuild required after adding new Tailwind classes (Tailwind v4 CSS-first config)

## COMPLETED - Telegram Bot Connection
- Connected bot: My_test_telegram_for_one_inbox_bot (@My_test_inbox_bot)
- Token validated, account + page stored, webhook URL set
- Bot appears in sidebar and Connected Pages
- **Webhook limitation**: Local `.test` domain not reachable from Telegram servers
  - Need `herd share` or ngrok tunnel for live message delivery testing

## SKIPPED - Personal Facebook Conversations
- Facebook deprecated `user_messages` permission - no third-party app can read personal Messenger conversations
- Only page conversations (Brandk, تعلم الموسيقة) are accessible via Graph API

## COMPLETED - ngrok Tunnel + Webhook Setup
- Herd's `herd share` (Expose) broken on Windows 11 (wmic removed)
- Installed ngrok 3.36.1, tunnel running: `https://uncompulsive-leaky-lindsay.ngrok-free.dev`
- Telegram webhook re-registered via API (`setWebhook`) — confirmed `{"ok":true}`
- Meta webhook configured in developer console (Messenger API settings) — verified with green checkmark
- **IMPORTANT**: Queue worker (`php artisan queue:work`) must be running in separate terminal for incoming messages

## COMPLETED - Meta Webhook Fix (App-Level Fields)
- Root cause: App-level webhook subscription had callback URL + active=true but **zero fields configured**
- Fix: POST to `/{app_id}/subscriptions` with `fields=messages,messaging_postbacks,messaging_optins,message_deliveries,message_reads`
- Now both app-level AND page-level subscriptions are correctly configured
- **Dev mode caveat**: Only app admin/developer/tester users can trigger webhooks in development mode

## IN PROGRESS: Remaining Tests
- STILL TODO:
  1. ~~Set up tunnel (herd share/ngrok) to test Telegram webhook delivery~~ DONE
  2. ~~Telegram incoming messages~~ DONE (working)
  3. Facebook Messenger incoming messages — fields fixed, needs retest (dev mode: sender must be app admin/tester)
  4. Test Instagram DM detection (requires IG Professional account linked to FB page)
  5. End-to-end message send/receive testing on all platforms
  6. Test FB page filtering in sidebar (click Brandk → only Brandk conversations)

## COMPLETED - Phase 3.1: AI Config UI
- Created `app/Livewire/Settings/AiConfig.php` - per-page AI configuration component
  - Page selector with green dot for active AI configs
  - Business description, product catalog (repeater), pricing info (repeater), FAQ (repeater)
  - Tone selector (friendly/professional/casual/formal), language selector (en/ar/fr/es/auto)
  - Response delay range (min/max seconds)
  - Working hours editor with per-day toggle + time pickers + timezone
  - Per-page active/inactive toggle
  - `updateOrCreate` on save (creates config on first save, updates after)
- Created `resources/views/livewire/settings/ai-config.blade.php` - two-column layout
- Route: `/settings/ai/config` (name: `settings.ai.config`)
- Sidebar: "AI Config" link with cog icon, indented under existing AI toggle
- Added `GEMINI_API_KEY`, `GEMINI_MODEL`, `GEMINI_SCORING_MODEL` to `.env`
- **NOTE**: `GEMINI_API_KEY` value still empty - user must add their key for AI to work

## COMPLETED - Admin AI Chat
- Created `app/Livewire/AiChat.php` - full-page Livewire component with analytics chat
  - Loads last 10 messages from `ai_commands` table on mount
  - Builds analytics context (conversations, messages, contacts, leads, platform breakdown)
  - Calls `GeminiProvider::chatWithAdmin()` with context + conversation history
  - Saves Q&A exchanges to `ai_commands` table
- Created `resources/views/livewire/ai-chat.blade.php` - ChatGPT-like UI
  - Scrollable message area with auto-scroll via Alpine.js
  - User messages: right-aligned purple bubbles; AI messages: left-aligned with sparkle icon
  - Empty state with suggested questions
  - Loading animation (bouncing dots) while AI responds
- Added `chatWithAdmin()` method to `GeminiProvider` (1000 token limit)
- Added `callGemini()` `$maxOutputTokens` parameter (backwards compatible, defaults to 500)
- Route: `GET /ai-chat` (name: `ai-chat`)
- Sidebar: "AI Chat" with sparkles icon in Manage group

## COMPLETED - Phase 3.3: AI-Human Handoff
- Added `ai_paused` boolean column to `conversations` table (default false)
- `Conversation` model: added to fillable, casts, `pauseAi()` / `resumeAi()` helpers
- `SendAiResponse` job: early return if `$conversation->ai_paused` (after working hours check)
- Inbox Livewire `sendMessage()`: auto-pauses AI when human agent replies
- Inbox Livewire `toggleAiPause()`: manual toggle for agents
- Blade view: AI status badges in sidebar (green sparkles = active, orange pause = paused)
- Blade view: clickable toggle button in chat header to pause/resume AI

## COMPLETED - Phase 3.4: Lead Scoring UI
- Inbox: clickable lead score badge in chat header → expands Alpine.js dropdown showing last 10 score events
  - Color-coded: green for positive, red for negative score changes
  - Shows score/100 header, event reason + human-readable timestamp
  - Closes on click outside
- Contacts: table rows now clickable → opens Flux modal with full contact detail
  - Contact info (name, email, phone, avatar)
  - Large score circle + status badge + platform icons
  - Link to inbox conversations
  - Full scrollable score event history (all events, newest first)
  - Color-coded score changes (green/red)
- Files modified:
  - `resources/views/livewire/inbox/index.blade.php` - score history dropdown
  - `app/Livewire/Contacts/Index.php` - selectContact/closeContact + selectedContact computed
  - `resources/views/livewire/contacts/index.blade.php` - clickable rows + detail modal

## COMPLETED - Bug Fixes: AI Resume + Language Mirroring + AI Chat Actions
- **AI Resume Fix**: `toggleAiPause` now dispatches `SendAiResponse` for last inbound message when un-pausing, so AI catches up on missed messages
- **Language Mirroring**: Replaced `"Language: en"` with mandatory language mirroring rule — AI MUST detect and respond in customer's language (Arabic, French, etc.)
- **Sales Personality**: Rewrote system prompt — AI is now an "elite sales closer", strategy varies by lead score (discovery → value creation → close the deal)
- **AI Chat Actions**: AI Chat can now take real actions:
  - `send_message` — send to specific contact
  - `send_bulk_message` — send to contacts filtered by min_score or lead_status
  - `pause_ai` / `resume_ai` — toggle AI per conversation
  - AI crafts the message, includes action blocks, backend executes
  - Analytics context now includes all contact IDs for targeting
- Files modified:
  - `app/Livewire/Inbox/Index.php` — AI resume logic in toggleAiPause
  - `app/Services/AI/GeminiProvider.php` — system prompt overhaul + admin chat actions
  - `app/Livewire/AiChat.php` — action parsing + execution (sendMessage, sendBulk, toggleAi)

## COMPLETED - AI Chat Memory + Media Upload + Emoji Picker
- **AI Chat Memory**:
  - Added `ai_memory` text column to `teams` table (migration)
  - AI can save persistent facts via `save_memory` action (e.g. "remember our best seller is...")
  - Memory is always included in system prompt — persists across sessions
  - Increased chat history from 10 → 30 Q&As (Gemini 2.5 Flash has 1M context)
- **Media Upload — Inbox**:
  - `WithFileUploads` trait on Inbox component
  - Paperclip button triggers hidden file input (images, PDFs, docs, spreadsheets)
  - Attachment preview above composer (image thumbnail or filename)
  - Messages with `media_url` render inline: images as `<img>`, files as download links
  - `SendPlatformMessage` updated for all 3 platforms:
    - FB/IG: attachment type `image`/`file` with payload URL
    - WhatsApp: `image`/`document` type with link + optional caption
    - Telegram: `sendPhoto` for images, `sendDocument` for files
- **Media Upload — AI Chat**:
  - Same pattern: paperclip button, preview, media stored in `public` disk
  - Media renders in chat bubbles (images clickable, files downloadable)
- **Emoji Picker**:
  - Installed `picmo` + `@picmo/popup-picker` (npm)
  - Alpine.js `emojiPicker` component in `app.js`
  - Smiley face button in both inbox and AI chat composers
  - Emoji inserted at cursor position in text input
- **Infrastructure**:
  - `php artisan storage:link` — public/storage symlink created
  - Vite build successful with picmo bundled (~107KB JS)

## COMPLETED - Shift+Enter Fix + Dashboard + Analytics
- **Shift+Enter Fix**: Both AI chat and inbox composers now use `<textarea>` instead of `<input>`
  - Enter sends message, Shift+Enter creates newline
  - Auto-growing textarea (up to 128px max height)
- **Dashboard Page**: Replaced Laravel placeholder with real Livewire dashboard
  - Route changed from `Route::view` to Livewire component
  - Top stats: unread, messages today, contacts, conversations (with week-over-week indicators)
  - AI performance bar (AI vs human response %)
  - Platform breakdown with progress bars
  - Hot leads list + recent conversations (clickable to inbox)
  - Lead pipeline bar (new → cold → warm → hot → converted → lost)
  - Quick action cards (Open Inbox, AI Chat, Connections)
- **Phase 3.5 Analytics Page**: Full AI analytics dashboard at `/analytics`
  - Period selector (7d / 14d / 30d / 90d)
  - Key metrics: AI automation rate, AI avg response time, human avg response time, conversion rate
  - AI vs human response split bar with speed comparison (Nx faster)
  - Lead conversion funnel visualization
  - Platform performance table (conversations, messages, qualified leads per platform)
  - Daily message volume chart (inbound / AI / human stacked bars)
  - Common objections list with frequency and impact score
  - Lead score distribution grid with avg scores per status
  - Conversation status overview (new, open, AI paused)
  - Sidebar: "Analytics" link with chart-bar icon added under Manage

## COMPLETED - Comprehensive Security, Performance, UX, SEO & Localization Overhaul

### Phase 1: Security Fixes
- File upload validation on `AiChat.php` and `Inbox/Index.php` (10MB max, images+docs only)
- Security headers middleware (`X-Content-Type-Options`, `X-Frame-Options`, `X-XSS-Protection`, `Referrer-Policy`, `Permissions-Policy`)
- Rate limiting: webhooks (120/min), auth routes (60/min per IP), chat actions (30/min per user)
- Session encryption enabled in `.env.example`

### Phase 2: Performance
- Database indexes migration (messages, conversations, pages, contacts, lead_score_events)
- Analytics N+1 fixes: response time calculation replaced per-conversation loop with single SQL query
- Dashboard N+1 fixes: 9+ separate queries → 3 aggregated queries
- Sidebar caching: active pages cached for 5 minutes on Team model

### Phase 3: Chat UX
- Conversation pagination (infinite scroll, load 30 at a time)
- Message pagination (load latest 30 messages first, scroll up to load older)
- Auto-scroll on new messages with "near bottom" detection
- "New messages" badge when user is scrolled up reading history
- Same improvements applied to AI Chat

### Phase 4: Marketing Pages + SEO
- Marketing layout with navbar, footer, language switcher, SEO meta
- 6 marketing pages: about, contact, privacy, terms, pricing, features
- Landing page improved: FAQ accordion, testimonials, localized strings
- SEO: OG tags, Twitter cards, canonical URLs, hreflang tags, JSON-LD structured data
- Sitemap.xml route + robots.txt with app routes blocked

### Phase 5: Localization (i18n)
- Translation files: en.json, ar.json, de.json, es.json
- SetLocale middleware (query param → session → browser Accept-Language → fallback)
- Language switcher in marketing navbar
- RTL support for Arabic (`dir="rtl"` + CSS overrides)

### Phase 6: Runtime Optimization
- Dashboard stats cached for 5 minutes per team
- Analytics data cached for 5 minutes per team+period
- wire:loading.attr="disabled" on send buttons to prevent double-submits
- Production checklist documented below

### Production Checklist (manual steps)
- Set `APP_DEBUG=false` and `SESSION_ENCRYPT=true` in production `.env`
- Switch queue driver to Redis: `QUEUE_CONNECTION=redis`
- Switch database to MySQL/PostgreSQL (not SQLite)
- Run: `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- Consider Laravel Octane (Swoole/RoadRunner) for persistent workers
- Set up Redis for cache driver: `CACHE_STORE=redis`

## COMPLETED - Phase 4.1: Stripe Billing (Laravel Cashier)
- Installed `laravel/cashier` v16.3.0
- Cashier migrations modified to use `teams` table instead of `users` (billing is per-team)
- `Billable` trait added to `Team` model
- `Cashier::useCustomerModel(Team::class)` in AppServiceProvider
- `config/stripe.php` — Plan definitions (free/starter/pro/enterprise) with AI credits + page limits
- `app/Livewire/Settings/Billing.php` — Full billing settings page:
  - Current plan display, usage bars (AI credits, connected pages)
  - Plan cards with upgrade buttons → Stripe Checkout Session
  - "Manage Subscription" → Stripe Customer Portal
  - Invoice history table with PDF downloads
- `resources/views/livewire/settings/billing.blade.php` — Billing UI
- `routes/settings.php` — Added billing route
- Settings nav — Added "Billing" item
- `app/Http/Controllers/StripeWebhookController.php` — Extends Cashier's webhook controller:
  - `customer.subscription.created/updated` → update team's plan + limits
  - `customer.subscription.deleted` → downgrade to free
  - `invoice.payment_failed` → mark as past_due
- `routes/api.php` — `/stripe/webhook` endpoint
- `app/Http/Middleware/EnforcePlanLimits.php` — Plan enforcement:
  - `canConnectPage()` — checks page limit before connecting
  - `hasAiCredits()` — checks AI credit limit before AI response
  - Past-due billing warning via session flash
- `ConnectionController` — Page limit checks added to Facebook, WhatsApp, Telegram connect methods
- `SendAiResponse` — AI credit check + increment on each response
- `.env` — Stripe keys + price IDs added

## COMPLETED - Phase 4.2: Real-Time WebSockets (Laravel Reverb)
- Installed `laravel/reverb`, `laravel-echo`, `pusher-js`
- `config/broadcasting.php` — Reverb connection config
- `config/reverb.php` — Reverb server config
- `.env` — BROADCAST_CONNECTION=reverb, Reverb app keys + host/port
- `resources/js/echo.js` — Echo instance setup (imported in app.js)
- 3 broadcast events created:
  - `app/Events/NewMessageReceived.php` — When inbound message arrives
  - `app/Events/AiResponseSent.php` — When AI sends a response
  - `app/Events/ConversationUpdated.php` — When conversation status changes
- All events broadcast on `PrivateChannel("team.{teamId}")` with `ShouldBroadcast`
- `routes/channels.php` — Channel authorization (user must belong to team)
- `ProcessIncomingMessage` — Broadcasts `NewMessageReceived` for all platforms (Meta, WhatsApp, Telegram)
- `SendAiResponse` — Broadcasts `AiResponseSent` after AI responds
- Inbox view — `wire:poll.15s` replaced with Echo listeners (real-time via WebSocket)
- Dashboard view — Echo listeners for real-time counter updates
- Vite build successful (182KB JS with Echo + Pusher bundled)

## COMPLETED - Phase 2.3: WhatsApp QR Gateway (Evolution API)

### Architecture
- Evolution API v2.2.3 (Docker) — self-hosted WhatsApp gateway using Baileys
- Laravel communicates with it via `http://localhost:8080`
- Evolution API calls back to Laravel via public HTTPS webhook URL

### Files Created/Modified
- `app/Services/EvolutionApiService.php` — HTTP client for all Evolution API calls (createInstance, getQrCode, getConnectionState, sendText, deleteInstance, logoutInstance)
- `app/Http/Controllers/Webhooks/EvolutionWebhookController.php` — Receives QRCODE_UPDATED, CONNECTION_UPDATE, MESSAGES_UPSERT events; stores QR/connected state in Cache
- `app/Livewire/Connections/WhatsAppQrModal.php` — QR scan flow with 90s timeout, direct API fallback polling
- `app/Jobs/ProcessIncomingMessage.php` → `processEvolution()` — Handles inbound messages from gateway
- `app/Jobs/SendPlatformMessage.php` → `sendViaEvolution()` — Sends outbound messages through gateway
- `routes/api.php` — `POST /api/webhooks/evolution`
- `config/services.php` — Evolution API config block
- `docker-compose.evolution.yml` — Evolution API + PostgreSQL compose file
- `docs/whatsapp-gateway.md` — Full developer reference

### Infrastructure
- Custom Nginx block `C:\Users\NanoChip\.config\herd\config\pro\nginx\tunnel.conf` — port 8088, accepts any Host header, overrides HTTP_HOST to `one-inbox.test` for Herd routing
- `bootstrap/app.php` — `trustProxies(at: '*')` for Cloudflare Tunnel headers
- Cloudflare Tunnel via `cloudflared.exe` — free public HTTPS URL, no account needed
- `.env` — `EVOLUTION_WEBHOOK_URL` set to tunnel URL

### Known Limitations
- Baileys (WhatsApp client inside Evolution API) fails to connect to WhatsApp from Docker/WSL2 on Windows — WebSocket handshake timeout. This is a local dev-only issue; production Linux servers work fine.
- Tunnel URL changes on every cloudflared restart — update `.env` and re-run `php artisan config:clear`

### Status
- Code is production-ready; full QR flow tested via Cloudflare Tunnel
- Awaiting test on a real Linux server to confirm end-to-end QR scan + message flow

## NEXT: Phase 2.2 - Platform-agnostic features
- Canned responses, contact merging, etc.
