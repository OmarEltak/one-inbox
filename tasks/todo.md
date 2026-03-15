# One Inbox - Unified Social Inbox + AI Sales Responder (SaaS)

## Project Overview

A multi-tenant SaaS application (Laravel 12 + Livewire 4) where businesses connect their social media accounts and manage ALL conversations from a single unified inbox. An AI sales agent responds directly in DMs, scores leads, and provides analytics to the marketing lead via a command-center chat.

**Business model:** SaaS subscription. Internal dogfooding first, then sell to other businesses.

---

## Architecture Decisions

### Stack
- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Livewire 4 + Flux UI + Tailwind CSS 4
- **Auth:** Laravel Fortify (already installed with 2FA)
- **Database:** SQLite for dev, MySQL/PostgreSQL for production
- **Queue:** Laravel Queues (database driver -> Redis for production)
- **Real-time:** Laravel Reverb (WebSockets) for live message updates
- **AI:** Provider-agnostic interface. Gemini 2.0 Flash (free) for internal testing, upgrade to Claude/GPT when going SaaS
- **Multi-tenant:** Single database, team-scoped (tenant = team/company)

### AI Provider Strategy
- **Phase 1 (internal testing):** Gemini 2.0 Flash (free tier: 15 RPM, 1M TPM) + Flash-Lite for scoring
- **Phase 2 (SaaS launch):** Upgrade to Claude Sonnet or GPT-4o, pass cost to customers
- **Architecture:** Provider-agnostic interface (`AiProviderInterface`) - swap providers via config, zero code changes
- Supported providers: Gemini, Claude, OpenAI (configurable per team/plan)
- Self-hosting open-source models costs MORE ($500-2000/month GPU) with WORSE quality

### Key Architectural Patterns
- **Platform Adapter Pattern** - Each platform implements `MessagingPlatformInterface`
- **Webhook-first** - Incoming messages via webhooks -> raw storage -> queued processing
- **Token Vault** - Encrypted storage for all platform access tokens
- **Conversation Threading** - Unified conversation model across all platforms
- **AI Pipeline** - Incoming message -> lead scoring -> AI response generation -> send

---

## Database Schema (Core Tables)

### `teams` (Multi-tenant)
```
id, name, slug, owner_id (user_id),
subscription_plan, subscription_status,
ai_enabled (bool, default true), ai_disabled_at (timestamp, nullable),
ai_credits_used, ai_credits_limit,
settings (json), timestamps
```

### `team_user` (pivot)
```
team_id, user_id, role (admin/agent/viewer), timestamps
```

### `connected_accounts`
```
id, team_id, platform (enum), platform_user_id, name, email, avatar,
access_token (encrypted), refresh_token (encrypted), token_expires_at,
scopes, metadata (json), is_active, connected_at, timestamps
```

### `pages` (Facebook/Instagram pages, WhatsApp business numbers, Telegram bots)
```
id, connected_account_id, team_id, platform, platform_page_id, name, avatar,
page_access_token (encrypted), category, is_active,
metadata (json), timestamps
```

### `contacts`
```
id, team_id, name, avatar, email, phone,
lead_score (0-100), lead_status (enum: new/warm/hot/cold/converted/lost),
score_history (json), tags (json),
first_seen_at, last_interaction_at, metadata (json), timestamps
```

### `contact_platform` (a contact can exist on multiple platforms)
```
id, contact_id, platform, platform_contact_id,
platform_name, platform_avatar, metadata (json), timestamps
```

### `conversations`
```
id, page_id, team_id, platform, platform_conversation_id,
contact_id, status (open/closed/snoozed/archived),
last_message_at, last_message_preview, unread_count,
assigned_to (user_id), labels (json),
metadata (json), timestamps
```

### `messages`
```
id, conversation_id, platform_message_id, direction (inbound/outbound),
sender_type (contact/user/ai), sender_id,
content_type (text/image/video/audio/file/location/template/interactive),
content (text), media_url, media_type,
reply_to_message_id, ai_confidence (float, null for human messages),
metadata (json), platform_sent_at, delivered_at, read_at, timestamps
```

### `webhook_logs`
```
id, team_id, platform, event_type, payload (json),
processed (bool), processed_at, error, timestamps
```

### `ai_configs` (per page)
```
id, page_id, team_id,
system_prompt, business_description, product_catalog (json),
pricing_info (json), faq (json),
tone (enum: professional/friendly/casual/formal),
language, response_delay_min_seconds, response_delay_max_seconds,
working_hours (json), timezone,
escalation_rules (json),
sales_methodology (json - qualifying questions, objection handling, closing techniques),
is_active, timestamps
```

### `lead_score_events`
Tracks why a contact's score changed.
```
id, contact_id, conversation_id, event_type,
score_change (+/-), reason, ai_analysis (text),
timestamps
```

### `ai_commands`
Admin command center history.
```
id, team_id, user_id, command (text), response (text),
action_taken (json - what the AI actually did),
contacts_affected (int), status (pending/processing/completed/failed),
timestamps
```

### `campaigns`
Bulk outreach campaigns triggered by admin via AI command center.
```
id, team_id, created_by (user_id), name, type (re_engagement/follow_up/promotion),
target_criteria (json - filters like date range, lead score, status),
message_template (text), ai_personalize (bool),
total_contacts, sent_count, reply_count,
status (draft/active/paused/completed), scheduled_at, timestamps
```

---

## Phase 1: Foundation + Meta Platforms

### 1.1 Core Infrastructure
- [ ] Multi-tenant setup (teams, team_user, middleware)
- [ ] Database migrations for all core tables
- [ ] Model classes with relationships and team scoping
- [ ] Platform adapter interface (`MessagingPlatformInterface`)
- [ ] Base webhook controller with signature verification
- [ ] Encrypted token storage service
- [ ] Queue job base classes for message processing
- [ ] Laravel Reverb setup for real-time updates

### 1.2 Facebook Messenger Integration
- [ ] Meta OAuth flow via Laravel Socialite
- [ ] Exchange short-lived token -> long-lived token -> page access tokens
- [ ] `GET /me/accounts` to list/store user's pages
- [ ] Subscribe pages to webhooks (messages, deliveries, reads)
- [ ] Webhook endpoint: verify challenge + process incoming messages
- [ ] Fetch conversation history on connect
- [ ] Send messages via `POST /{PAGE_ID}/messages`
- [ ] Handle 24-hour messaging window + HUMAN_AGENT tag
- [ ] Multi-page support

### 1.3 Instagram DM Integration
- [ ] Detect linked Instagram Professional accounts from FB pages
- [ ] Shared page access token (IG DMs route through FB Page)
- [ ] `GET /{PAGE_ID}/conversations?platform=instagram`
- [ ] Webhook events for Instagram messages
- [ ] Handle IG constraints (1000-follower min, 200 DM/hr)
- [ ] Media message support

### 1.4 WhatsApp Business Integration
- [ ] WABA setup flow in app
- [ ] Phone number registration and verification
- [ ] System user token storage
- [ ] Webhook for incoming WhatsApp messages
- [ ] Send session messages (within 24h)
- [ ] Template message support (pre-approved outbound)
- [ ] Media messages (images, documents, voice notes)

### 1.5 Unified Inbox UI
- [ ] Layout: sidebar (conversations) + main (thread) + right panel (contact + lead score)
- [ ] Filters: All, Facebook, Instagram, WhatsApp, Unread, Assigned to me, Hot leads
- [ ] Real-time updates via Reverb
- [ ] Message composer with text + media upload
- [ ] Platform badges (FB/IG/WA icons)
- [ ] Contact info panel with lead score gauge (0-100, color-coded)
- [ ] Conversation status management
- [ ] Page switcher for multi-page users
- [ ] Unread count badges
- [ ] Search across all conversations
- [ ] Mobile-responsive

---

## Phase 2: Telegram + Platform Features

### 2.1 Telegram Bot Integration
- [ ] BotFather setup guide in app
- [ ] Store bot token, set webhook
- [ ] Send/receive messages + media
- [ ] Free, no approval needed

### 2.2 Platform-Agnostic Features
- [ ] Contact merging across platforms (same person on FB + IG + WA)
- [ ] Conversation assignment to team members
- [ ] Internal notes on conversations
- [ ] Canned responses / quick reply templates
- [ ] Labels/tags for conversations
- [ ] Basic analytics dashboard (response times, volume, by platform)

---

## Phase 3: AI Sales Responder

### 3.1 AI Engine Core
- [ ] Provider-agnostic AI interface (`AiProviderInterface`):
  - `GeminiProvider` - free tier for internal testing (Gemini 2.0 Flash + Flash-Lite)
  - `ClaudeProvider` - for SaaS upgrade (Sonnet for conversations, Haiku for scoring)
  - `OpenAiProvider` - alternative option
  - Config-driven: swap provider per team or globally, zero code changes
- [ ] System prompt builder that combines:
  - Business description + product catalog
  - Sales methodology (SPIN selling adapted for DMs)
  - Conversation history (last N messages for context)
  - Contact's lead score + history
  - Platform-specific tone adjustments

- [ ] **AI Global Kill Switch (team-level, head admin only):**

  One toggle on the `teams` table: `ai_enabled` (bool).
  Only the head admin (team owner) can flip it.

  - **AI ON** (default): AI auto-responds on ALL pages, ALL conversations (new + existing),
    with configurable delay (30s-3min to feel human).
  - **AI OFF**: AI stops responding everywhere instantly. Inbox still works normally,
    agents respond manually. Used when AI has a bug - agents report to head admin,
    head admin hits the kill switch, contacts the developer. Once fixed, head admin
    flips it back on.

  **Critical: Lead scoring and analysis runs ALWAYS, even when AI is OFF.**
  When AI is OFF, it still:
  - Analyzes every incoming message in the background
  - Updates lead scores in real-time
  - Tags conversations with detected intent
  - Shows insights in the contact side panel
  - It just doesn't send responses - it's an invisible data assistant

- [ ] Sales techniques baked into prompts:
  - Ask qualifying questions (budget, timeline, needs, decision maker)
  - Create urgency and need ("limited spots", "price goes up next week")
  - Handle objections with empathy + reframing
  - Push toward conversion (booking, purchase, signup)
  - Follow up on silence (after X hours with no reply)
- [ ] Human escalation detection (AI notifies humans, never self-decides to change mode):
  - Angry/frustrated customer -> notify human agent
  - Complex technical question outside AI knowledge -> flag for human
  - High-value deal (score > 80) -> notify for human oversight
  - Customer explicitly asks for human -> notify + auto-switch to Human Only
- [ ] Response delay simulation (random 30s-3min to feel natural, configurable)

### 3.2 Lead Scoring System
- [ ] Automatic scoring (0-100) based on conversation signals:

  | Signal | Score Change | Category |
  |---|---|---|
  | First message received | +5 | Engagement |
  | Asked about pricing | +20 | High intent |
  | Asked about availability | +15 | High intent |
  | Responded within 5 min | +10 | Engagement |
  | Mentioned a competitor | +10 | Shopping = interested |
  | Asked for discount | +15 | Wants to buy |
  | Shared contact info | +25 | Very high intent |
  | Requested meeting/call | +30 | Ready to close |
  | Multiple sessions (came back) | +15 | Persistent interest |
  | Went silent after pricing | -10 | Cooling off |
  | Said "not interested" | -30 | Disengaged |
  | Said "too expensive" | +5 | Still engaged (objection) |
  | No reply in 48h | -5 | Cooling off |
  | Replied after follow-up | +20 | Re-engaged |

- [ ] Score stored on contact, updated in real-time
- [ ] Score history with reasons (audit trail in `lead_score_events`)
- [ ] Visual display: color-coded badge in inbox
  - 0-25: Gray (cold)
  - 26-50: Blue (cool)
  - 51-70: Yellow (warm)
  - 71-85: Orange (hot)
  - 86-100: Red/Fire (ready to close)
- [ ] AI uses score to adjust conversation strategy:
  - Low score: Focus on discovery, build rapport
  - Mid score: Create need, handle objections
  - High score: Push for close, offer incentives
- [ ] Lead status auto-transitions:
  - New -> Warm (score > 30)
  - Warm -> Hot (score > 70)
  - Hot -> Converted (purchase/booking confirmed)
  - Any -> Cold (no interaction in X days)
  - Any -> Lost (explicitly declined)

### 3.3 Admin AI Command Center
The marketing lead gets a chat interface to command the AI:

- [ ] Natural language command processing (Claude interprets intent)
- [ ] **Bulk outreach campaigns:**
  - "Reach out to everyone who talked to us in January but didn't buy"
  - "Follow up with all warm leads who went silent in the last 2 weeks"
  - "Send a promotion to all contacts scored above 50"
  - AI generates personalized messages per contact based on their conversation history
  - Creates campaign record, sends messages with rate limiting
  - Reports progress and results

- [ ] **Analytics queries:**
  - "How many interested leads came this month?"
  - "What are the top objections people give?"
  - "Compare this month's conversion rate to last month"
  - "Which platform brings the most qualified leads?"
  - "Show me all hot leads that haven't been contacted today"
  - AI queries the database and returns formatted insights

- [ ] **Lead management:**
  - "Show me the hottest leads right now"
  - "Who should we follow up with today?"
  - "Mark all leads from campaign X as lost"

- [ ] **AI tuning:**
  - "The AI is being too aggressive on pricing, tone it down"
  - "Add this FAQ: [question] -> [answer]"
  - "When people ask about returns, say [policy]"
  - Updates ai_configs based on natural language instructions

- [ ] Command history with audit trail (what was asked, what was done, who was affected)
- [ ] Safety: Bulk actions require confirmation ("This will message 47 contacts. Proceed?")

### 3.4 AI-Human Handoff
- [ ] Human can take over any conversation instantly
- [ ] AI auto-pauses when human starts typing
- [ ] When human takes over, AI provides summary:
  - "Customer interested in [product], asked about [pricing], main objection is [X], lead score: 78"
- [ ] Human can hand back to AI: "AI, take over from here"
- [ ] Notification system for escalations (in-app + optional email/push)

### 3.5 AI Analytics
- [ ] Conversations handled: AI vs human breakdown
- [ ] AI conversion rate vs human conversion rate
- [ ] Average response time (AI vs human)
- [ ] Lead score distribution over time
- [ ] Top performing AI conversation patterns
- [ ] Common objections + how AI handled them
- [ ] Revenue attribution (AI-driven vs human-driven if tracking conversions)

---

## Phase 4: SaaS + Scale

### 4.1 Multi-Tenant SaaS
- [ ] Subscription plans (Free trial, Starter, Pro, Enterprise)
- [ ] Plan limits (conversations/month, AI credits, team members, connected pages)
- [ ] Billing integration (Stripe via Laravel Cashier)
- [ ] Usage tracking and overage handling
- [ ] Onboarding flow for new teams

### 4.2 Team Management
- [ ] Invite users via email
- [ ] Roles: Admin (full access + AI command center), Agent (inbox + respond), Viewer (read-only)
- [ ] Conversation assignment and routing rules
- [ ] Team performance metrics

### 4.3 Polish
- [ ] Notification system (in-app, email, browser push)
- [ ] Conversation SLA tracking
- [ ] Export conversations (CSV, PDF)
- [ ] Webhook retry/replay system
- [ ] Rate limit handling per platform
- [ ] Comprehensive logging and monitoring
- [ ] Landing page / marketing site

---

## Platform API Summary

| Platform | Cost | Setup Time | Feasibility |
|---|---|---|---|
| **Facebook Messenger** | Free | 2-6 weeks (app review) | Excellent |
| **Instagram DMs** | Free | 2-6 weeks (app review) | Excellent |
| **WhatsApp Business** | Per-message (free inbound 24h) | 1-4 weeks | Excellent |
| **Telegram** | Free | Immediate | Excellent |

*Twitter/X, LinkedIn, Snapchat, TikTok - skipped for now (cost/feasibility issues)*

---

## Build Order

1. **Phase 1.1** - Core infrastructure (multi-tenant, migrations, models, interfaces)
2. **Phase 1.5** - Inbox UI shell (visual testing before API wiring)
3. **Phase 1.2** - Facebook Messenger (reference implementation)
4. **Phase 1.3** - Instagram DMs (80% shared with Facebook)
5. **Phase 1.4** - WhatsApp Business
6. **Phase 2.1** - Telegram
7. **Phase 3.1** - AI engine core (Claude API + response pipeline)
8. **Phase 3.2** - Lead scoring system
9. **Phase 3.3** - Admin AI command center
10. **Phase 3.4** - AI-human handoff
11. **Phase 3.5** - AI analytics
12. **Phase 2.2** - Platform-agnostic features (merging, canned responses, etc.)
13. **Phase 4** - SaaS billing, teams, polish

---

## Technical Prerequisites Before Coding

1. **Meta Developer Account** - developers.facebook.com
2. **Meta Business Verification** - Required for app review
3. **Facebook Page** - At least one for testing
4. **Instagram Professional Account** - Linked to FB page
5. **WhatsApp Business Account** - Fresh phone number
6. **SSL/HTTPS** - ngrok for local dev (webhooks require HTTPS)
7. **Google AI API Key** - For Gemini free tier (Phase 3, internal testing). Upgrade to Anthropic/OpenAI for SaaS
8. **Stripe Account** - For SaaS billing (Phase 4)

---

## Review

*To be filled after implementation*
