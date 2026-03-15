# WhatsApp QR Gateway — Developer Reference

Allows users to connect any WhatsApp Business number by scanning a QR code,
with no Meta Business Manager setup required.

Built on **Evolution API v2** (self-hosted, same server as Laravel).

---

## Architecture

```
User's Phone (WhatsApp Business app)
        │ QR scan
        ▼
Evolution API  (port 8080, internal only)
        │ webhooks (POST /api/webhooks/evolution)
        ▼
Laravel (One Inbox)
        │ real-time
        ▼
Livewire Inbox
```

Evolution API runs as a Docker container on the same server.
Laravel communicates with it via `http://localhost:8080`.
Evolution API calls back to Laravel via `EVOLUTION_WEBHOOK_URL` (public HTTPS URL).

---

## Environment Variables

```env
# Production
EVOLUTION_API_URL=http://localhost:8080
EVOLUTION_API_KEY=your-global-api-key
EVOLUTION_WEBHOOK_URL=https://yourdomain.com/api/webhooks/evolution
EVOLUTION_WEBHOOK_HOST=                        # Leave empty in production

# Local dev (Herd on Windows)
# EVOLUTION_API_URL=http://localhost:8080
# EVOLUTION_WEBHOOK_URL=http://host.docker.internal/api/webhooks/evolution
# EVOLUTION_WEBHOOK_HOST=one-inbox.test         # Overrides Host header for Herd's nginx vhost matching
# Note: Baileys may fail to generate QR from Docker/WSL2 (WhatsApp connection timeout).
#       The code is production-ready; test the full QR flow on a real server.
```

---

## Evolution API Setup (Docker)

```bash
docker run -d \
  --name evolution-api \
  -p 8080:8080 \
  -e AUTHENTICATION_API_KEY=your-global-api-key \
  -e AUTHENTICATION_EXPOSE_IN_FETCH_INSTANCES=true \
  -v evolution_instances:/evolution/instances \
  atendai/evolution-api:v2.3.7
```

Or using docker-compose — see Evolution API docs.

Keep Evolution API running with Supervisor:

```ini
[program:evolution-api]
command=docker start -a evolution-api
autostart=true
autorestart=true
stderr_logfile=/var/log/supervisor/evolution-api.err.log
stdout_logfile=/var/log/supervisor/evolution-api.out.log
```

---

## Laravel Files Reference

| File | Purpose |
|------|---------|
| `app/Services/EvolutionApiService.php` | HTTP client wrapper for all Evolution API calls |
| `app/Http/Controllers/Webhooks/EvolutionWebhookController.php` | Receives and routes webhook events |
| `app/Livewire/Connections/WhatsAppQrModal.php` | QR scan flow — creates instance, polls for QR and connection |
| `app/Jobs/ProcessIncomingMessage.php` → `processEvolution()` | Processes inbound messages from gateway |
| `app/Jobs/SendPlatformMessage.php` → `sendViaEvolution()` | Sends outbound messages through gateway |
| `routes/api.php` | Webhook route: `POST /api/webhooks/evolution` |
| `config/services.php` | Evolution API config block |

---

## Data Model

Gateway connections reuse the existing `ConnectedAccount` and `Page` models.
They are identified by `metadata.gateway_mode = true`.

| Model | Field | Value |
|-------|-------|-------|
| `ConnectedAccount` | `platform` | `whatsapp` |
| `ConnectedAccount` | `platform_user_id` | Evolution instance name (e.g. `team_1_xk92pq3a`) |
| `ConnectedAccount` | `access_token` | Instance API key (encrypted) |
| `ConnectedAccount` | `metadata.gateway_mode` | `true` |
| `Page` | `platform` | `whatsapp` |
| `Page` | `platform_page_id` | Evolution instance name |
| `Page` | `page_access_token` | Instance API key (encrypted) |
| `Page` | `metadata.gateway_mode` | `true` |
| `Page` | `metadata.gateway_instance` | Evolution instance name |
| `Page` | `metadata.phone_number` | Connected phone number |

---

## Evolution API Endpoints Used

All use header: `apikey: <key>`

| Method | Endpoint | Used in | Purpose |
|--------|----------|---------|---------|
| POST | `/instance/create` | `EvolutionApiService::createInstance()` | Create new WhatsApp instance |
| GET | `/instance/connect/{instance}` | `EvolutionApiService::getQrCode()` | Fetch QR code |
| GET | `/instance/connectionState/{instance}` | `EvolutionApiService::getConnectionState()` | Check if connected |
| POST | `/message/sendText/{instance}` | `EvolutionApiService::sendText()` | Send text message |
| DELETE | `/instance/logout/{instance}` | `EvolutionApiService::logoutInstance()` | Disconnect session only |
| DELETE | `/instance/delete/{instance}` | `EvolutionApiService::deleteInstance()` | Remove instance fully |

---

## Webhook Events Handled

Route: `POST /api/webhooks/evolution`

| Event | Handler | What it does |
|-------|---------|--------------|
| `QRCODE_UPDATED` | `handleQrUpdated()` | Stores base64 QR image in cache (TTL 30s) for Livewire poll |
| `CONNECTION_UPDATE` state=`open` | `handleConnectionUpdate()` | Stores connected phone/name in cache, marks page active |
| `CONNECTION_UPDATE` state=`close`/`refused` | `handleConnectionUpdate()` | Marks page inactive |
| `MESSAGES_UPSERT` | `handleMessageUpsert()` | Creates WebhookLog, dispatches ProcessIncomingMessage |

### QRCODE_UPDATED payload
```json
{
  "event": "QRCODE_UPDATED",
  "instance": "team_1_xk92pq3a",
  "data": {
    "qrcode": {
      "base64": "data:image/png;base64,...",
      "pairingCode": "WZYEH1YY"
    }
  }
}
```
QR limit reached (user didn't scan in ~3 min):
```json
{ "data": { "statusCode": 500, "message": "QR code limit reached..." } }
```

### CONNECTION_UPDATE payload (connected)
```json
{
  "event": "CONNECTION_UPDATE",
  "instance": "team_1_xk92pq3a",
  "data": {
    "state": "open",
    "wuid": "201012345678@s.whatsapp.net",
    "profileName": "My Business"
  }
}
```

### MESSAGES_UPSERT payload (inbound text)
```json
{
  "event": "MESSAGES_UPSERT",
  "instance": "team_1_xk92pq3a",
  "data": {
    "key": {
      "remoteJid": "201099887766@s.whatsapp.net",
      "fromMe": false,
      "id": "3EB0C767D097A73422F0"
    },
    "pushName": "Ahmed",
    "messageType": "conversation",
    "message": { "conversation": "Hello!" },
    "messageTimestamp": 1741500000
  },
  "apikey": "<instance-apikey>"
}
```

---

## QR Connection Flow

1. User clicks **"Connect via QR Code"** on Connections page
2. `WhatsAppQrModal::startConnection()` calls `EvolutionApiService::createInstance()`
3. Evolution API creates instance and emits `QRCODE_UPDATED` webhook
4. `EvolutionWebhookController` stores base64 QR in `Cache::put("evo_qr_{instance}", ...)`
5. Livewire modal polls `WhatsAppQrModal::poll()` every 2 seconds
6. Modal displays QR image from cache
7. User scans QR with WhatsApp Business app
8. Evolution API emits `CONNECTION_UPDATE` with `state: "open"`
9. Controller stores phone/name in `Cache::put("evo_connected_{instance}", ...)`
10. Next Livewire poll detects connected state → calls `saveConnection()`
11. `saveConnection()` creates `ConnectedAccount` + `Page` in database
12. Modal shows success screen, dispatches `gateway-connected` to refresh Connections page

---

## Troubleshooting

### QR code never appears
- Check Evolution API is running: `curl http://localhost:8080/instance/fetchInstances -H "apikey: your-key"`
- Check `EVOLUTION_API_URL` and `EVOLUTION_API_KEY` in `.env`
- Check Evolution API logs for errors: `docker compose -f docker-compose.evolution.yml logs -f evolution-api`
- **Local dev on Windows/WSL2**: Baileys (the WhatsApp client library inside Evolution API) may fail to connect to WhatsApp servers from Docker containers on Windows with a "Timed Out" / "error in validating connection" error. This is a known Docker/WSL2 networking issue — WhatsApp's servers reject connections from certain Docker network fingerprints. **This only affects local dev.** On a real Linux server in production, it works fine.
- **Workaround for local dev**: Test by deploying to a VPS/staging server even briefly, or use ngrok to expose your local Laravel to the internet and set the webhook URL accordingly.

### QR appears but scanning doesn't connect
- Make sure `EVOLUTION_WEBHOOK_URL` is publicly accessible from the internet (not `localhost`)
- Check Laravel logs: `tail -f storage/logs/laravel.log | grep Evolution`
- Check the webhook is hitting Laravel: look for entries in `webhook_logs` table with `platform = whatsapp_gateway`

### Messages not appearing in inbox
- Check `webhook_logs` table for `platform = whatsapp_gateway` entries
- Check if `ProcessIncomingMessage` jobs are running: `php artisan queue:work`
- Look for the instance name in `pages` table: `SELECT * FROM pages WHERE platform_page_id LIKE 'team_%'`

### "Connected but failed to save" error
- Usually a database constraint issue — check `connected_accounts` unique constraint on `(team_id, platform, platform_user_id)`
- Check Laravel logs for the exact exception

### WhatsApp number gets disconnected randomly
- Phone went offline or WhatsApp was closed too long
- `CONNECTION_UPDATE state=close` fires → page marked inactive
- User needs to scan QR again — click "Connect via QR Code" again for that number

### Evolution API updates and breaks something

| What changed | Where to fix |
|---|---|
| Auth header (`apikey:` → something else) | `EvolutionApiService::headers()` and `instanceHeaders()` |
| Create instance endpoint path or body | `EvolutionApiService::createInstance()` |
| Send text endpoint or response | `EvolutionApiService::sendText()` |
| Webhook payload structure | `EvolutionWebhookController` event handlers |
| Inbound message payload fields | `ProcessIncomingMessage::processEvolution()` |
| JID format (e.g. suffix changes) | `processEvolution()` line: `str_replace('@s.whatsapp.net', '', $remoteJid)` |
| QR code field in webhook | `handleQrUpdated()` line: `$data['qrcode']['base64']` |
| Connection state field | `handleConnectionUpdate()` line: `$data['state']` |

---

## Upgrading Evolution API

1. Pull the new Docker image: `docker pull atendai/evolution-api:vX.X.X`
2. Stop the old container: `docker stop evolution-api && docker rm evolution-api`
3. Run the new container with the same `-v evolution_instances:/evolution/instances` mount (preserves sessions)
4. Test with one connection before rolling out

---

*Last updated: 2026-03-09 | Evolution API version: v2.3.x*
