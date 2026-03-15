# Full App Testing Plan - 2026-02-26

## Prerequisites
- [x] Fix `.env` APP_URL to `http://one-inbox.test` (was old domain)
- [x] Fix `.env` APP_NAME to `One Inbox` (was `Laravel`)
- [ ] Install tunneling tool for webhook testing (ngrok recommended)
- [ ] Clear config cache after .env changes

## Phase A: Basic App Health
1. [ ] Clear caches (`config:clear`, `cache:clear`, `route:clear`, `view:clear`)
2. [ ] Verify `http://one-inbox.test` loads the welcome page
3. [ ] Test registration flow (create new account)
4. [ ] Test login flow
5. [ ] Test team creation (required after first login)
6. [ ] Verify dashboard loads after team creation
7. [ ] Test navigation: Dashboard, Inbox, Contacts, Connections, AI Settings

## Phase B: Telegram Bot Integration (Easiest to test first)
1. [ ] Create a Telegram bot via @BotFather (or use existing one)
2. [ ] Navigate to Connections page
3. [ ] Click "Connect Telegram" and enter bot token
4. [ ] Verify bot connects successfully (green status shown)
5. [ ] Set up tunnel (ngrok) so Telegram can deliver webhooks
6. [ ] Update APP_URL to ngrok URL temporarily
7. [ ] Re-register Telegram webhook with public URL
8. [ ] Send a message to the bot from personal Telegram
9. [ ] Run queue worker to process incoming message
10. [ ] Verify message appears in Inbox
11. [ ] Reply from Inbox UI
12. [ ] Verify reply arrives in Telegram

## Phase C: Facebook/Instagram Integration
1. [ ] Go to developers.facebook.com → One Inbox app
2. [ ] Update Facebook Login redirect URI to `http://one-inbox.test/connections/facebook/callback`
3. [ ] Disable "Enforce HTTPS" in Facebook Login settings
4. [ ] Add required permissions to Facebook Login use case
5. [ ] Navigate to Connections page → "Connect with Facebook"
6. [ ] Complete OAuth flow, grant page permissions
7. [ ] Verify connected status shows with page count
8. [ ] Verify Instagram auto-detect (if IG linked to FB page)
9. [ ] Set up Meta webhook URL (needs public tunnel URL)
10. [ ] Configure webhook subscriptions (messages, deliveries, reads)
11. [ ] Send a test message to the Facebook page
12. [ ] Verify message appears in Inbox
13. [ ] Reply from Inbox UI
14. [ ] Verify reply arrives in Facebook Messenger

## Phase D: Inbox & Messaging
1. [ ] Verify conversation list shows all platforms
2. [ ] Test platform filter tabs (All, Facebook, Instagram, WhatsApp, Telegram)
3. [ ] Test search functionality
4. [ ] Select a conversation, verify messages load
5. [ ] Send a message from inbox UI
6. [ ] Verify message sends to correct platform
7. [ ] Test unread count badge
8. [ ] Verify contact info panel shows lead score

## Phase E: Contacts & AI Settings
1. [ ] Navigate to Contacts page, verify contacts from conversations appear
2. [ ] Verify lead scores show with color coding
3. [ ] Navigate to AI Settings
4. [ ] Test AI kill switch toggle
5. [ ] Verify only team owner sees AI settings

## Phase F: Error Handling & Edge Cases
1. [ ] Test disconnecting a platform
2. [ ] Test reconnecting
3. [ ] Verify error messages display properly
4. [ ] Test with empty inbox (no conversations)

## Known Blockers
- **Webhooks require public URL**: Both Telegram `setWebhook` and Meta webhooks need HTTPS public endpoint
  - Solution: Install ngrok (`npm install -g ngrok` or download from ngrok.com)
  - Alternative: Herd Pro "Share" feature
- **Facebook OAuth redirect**: Must match exactly what's registered in Meta Developer Console
- **Queue worker**: Must be running for incoming messages to process (`php artisan queue:work`)

## Current Status
- Starting Phase A (Basic App Health)
