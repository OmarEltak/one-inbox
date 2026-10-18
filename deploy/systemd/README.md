# Systemd units

Canonical copies of the five queue/websocket systemd units that run on prod
(`187.77.67.94`). Keep this directory in sync with `/etc/systemd/system/*.service`
on the server. If you edit a unit on the server, update the file here in the
same commit — otherwise the next person to clone + redeploy will roll back
your change.

The 2026-10-18 session uncovered that `one-inbox-queue.service` had been
stopped for 18 hours with no monitoring alert — version-controlling these
units + the new worker-liveness check in `capacity:health-check` is the
pre-emptive fix for that class of incident.

## Units

| File | Consumes | Who runs it |
|---|---|---|
| `one-inbox-queue.service` | **urgent, default, heavy-analysis, comments-ingest, comments-send** | main worker (the only consumer for the slow queues) |
| `one-inbox-queue@.service` | urgent, default (instances @1..@4) | 4 parallel workers for the fast queues |
| `one-inbox-queue-campaigns.service` | campaigns | bulk-send worker (isolated from urgent) |
| `one-inbox-whisper.service` | transcription | Groq/whisper worker |
| `one-inbox-reverb.service` | — | Reverb WebSocket on port 8080 |

## Deploying a changed unit

```bash
scp deploy/systemd/<file>.service root@187.77.67.94:/etc/systemd/system/
ssh root@187.77.67.94 "systemctl daemon-reload && systemctl restart <unit>"
ssh root@187.77.67.94 "systemctl is-active <unit>"
```

Also update the matching row in `docs/OT1_LIMITS.md` §4 if the queue list
changes.

## `one-inbox-queue-campaigns.service`

Dedicated worker for the `campaigns` queue. Bulk WhatsApp/email campaign
sends run on this worker so they never share capacity with the `urgent`
queue (customer webhooks, conversational AI). This isolation is
architectural — see
`docs/superpowers/specs/2026-08-26-bulk-multichannel-campaigns-design.md`
"Resource Control".

### Production (VPS `187.77.67.94`) — install once

```
sudo cp deploy/systemd/one-inbox-queue-campaigns.service /etc/systemd/system/
sudo mkdir -p /var/log/one-inbox && sudo chown deploy:deploy /var/log/one-inbox
sudo systemctl daemon-reload
sudo systemctl enable --now one-inbox-queue-campaigns
sudo systemctl status one-inbox-queue-campaigns
```

Follow logs:
```
sudo journalctl -u one-inbox-queue-campaigns -f
# or
tail -f /var/log/one-inbox/queue-campaigns.log
```

### Local dev (Windows/Herd)

Run in a dedicated terminal, in addition to the existing `queue:work`
terminal that handles `urgent` and `default`:

```
php artisan queue:work --queue=campaigns --sleep=5
```

Do NOT add `campaigns` to the general `queue:work` invocation. Queue
isolation is the whole point.

### Rollback

```
sudo systemctl disable --now one-inbox-queue-campaigns
sudo rm /etc/systemd/system/one-inbox-queue-campaigns.service
sudo systemctl daemon-reload
```

Removing the unit leaves any queued jobs in the `jobs` table untouched;
they will be picked up again as soon as the worker returns.
