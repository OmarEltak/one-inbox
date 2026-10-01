# 04 — Data handling questionnaire

The July answer to "processors" was **No**, but message content does leave our server: AI
replies send conversation text to our AI provider, and everything runs on a hosting
provider. Answering "No" while a reviewer can see AI replies in the video is an
inconsistency worth avoiding. Recommended answers (confirm the bracketed items):

| Question | Answer |
|---|---|
| Do you have data processors or service providers with access to Platform Data? | **Yes** |
| List them | `[Hosting provider of VPS 187.77.67.94 — confirm name]` (application + database hosting); `NaraRouter` AI gateway and the model provider(s) it routes to `[confirm: e.g. Anthropic / Google]` (generating optional AI replies from message text); `Cloudflare` `[only if ot1-pro.com is proxied through it — confirm]` |
| Who is responsible (data controller)? | `OT1 Pro (One Inbox) — Omar Mohamed` (as before; use the legal entity name if a company exists) |
| Country | `EG` |
| Disclosed personal data to public authorities for national-security requests in the last 12 months? | **No** (zero requests) |
| Policies for public-authority requests | Keep **Review of legality**; also tick **Data minimization** and **Documentation of requests** if offered (they're true: we only hold what's needed to show the inbox, and would log any request) |

Also verify before submitting:
- Privacy policy URL: https://ot1-pro.com/privacy — mentions Facebook/Instagram messages,
  AI processing, retention, and how to request deletion.
- Data deletion: callback `https://ot1-pro.com/api/webhooks/meta/data-deletion` (status page
  `/data-deletion/status/{code}`).
