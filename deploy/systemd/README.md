# Systemd units

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
