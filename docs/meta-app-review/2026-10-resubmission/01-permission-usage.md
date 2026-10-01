# 01 — Permission usage text (paste into each permission's box, English)

Every block ends with **Response to previous review** — keep it; reviewers check that the
new screencast addresses their note. "Video N" refers to `02-screencast-scripts.md`.

---

## Common app description (if a general "describe your app" box exists)

OT1-Pro (https://ot1-pro.com) is a shared team inbox for small businesses. A business
connects its Facebook Page and Instagram professional account and answers all customer
conversations from one screen. Incoming Messenger and Instagram Direct messages appear in
the OT1-Pro inbox in real time; staff reply from the inbox and the reply is delivered to the
customer in Messenger or Instagram. Businesses can optionally enable an AI assistant that
drafts or sends first replies using information the business provides; staff can pause it
per conversation and take over at any time. We only access assets the business explicitly
selects during Facebook/Instagram login, only respond to customer-initiated conversations,
and respect the 24-hour messaging window. We do not send broadcasts over Messenger or
Instagram.

---

## pages_show_list
After the business logs in with Facebook Login for Business and selects its Page(s), we call
`GET /me/accounts` to list exactly the Pages it granted, so it can see them on the OT1-Pro
Connections screen and we can obtain each Page's access token to receive and send its
messages. We don't use it for any Page the user didn't select.
**Response to previous review:** Video 1 shows the full Meta login, the Page-selection step
with the Page name visible, and the same Page then listed as Active on OT1-Pro's Connections
screen.

## business_management
Businesses whose Pages and Instagram accounts are owned by a Meta Business portfolio must
grant `business_management` during Facebook Login for Business so the selected
business-owned Page appears in `/me/accounts` and its Instagram account can be connected. We
only read the assets the user selected; we never create, edit or delete business assets,
users or ad accounts.
**Response to previous review:** Video 1 and Video 2 show Facebook Login for Business with
the business portfolio's Page selected and then connected in OT1-Pro.

## pages_read_engagement
Used to read basic Page metadata needed to run the inbox: the Page's name and picture
(shown on Connections and in the inbox), and the Instagram professional account linked to
the Page (`instagram_business_account` field), which is how we connect the business's
Instagram DMs. We also load recent conversation history when a Page is first connected so
staff see context.
**Response to previous review:** Video 1 shows the Page name/picture appearing in OT1-Pro
after login; Video 2 shows the linked Instagram account being detected from the same Page.

## pages_manage_metadata
Used to subscribe the business's Page to our app's webhooks
(`POST /{page-id}/subscribed_apps` with `messages`, `message_echoes`,
`message_deliveries`, `message_reads`, `messaging_postbacks`, `feed`) immediately after the
business connects it. Without this subscription, new customer messages never reach the
business's inbox.
**Response to previous review:** As requested, Video 1 (part B) shows (1) where the
subscription happens — right after connecting the Page, our Page diagnostics screen shows
the Page's `subscribed_apps` entry for our app with the subscribed fields — and (2) a sample
webhook event: a message sent from a phone to that **same Page** appears as a new webhook
entry (timestamp, Page id) and as a new conversation in the inbox.

## pages_messaging
Used to deliver the business's replies to customers who messaged its Page: staff type a
reply in the OT1-Pro inbox and we call `POST /{page-id}/messages` with the customer's PSID.
Optional AI replies use the same endpoint. Replies are only sent within the 24-hour window
of a customer-initiated conversation.
**Response to previous review:** Video 1 shows (1) asset selection with the Page visible,
(2) a live send from the OT1-Pro inbox, and (3) the same message delivered in the Messenger
app on a phone.

## instagram_basic
Used to read the connected Instagram professional account's id, username and profile
picture (via the Page's linked `instagram_business_account`) so the business can see which
Instagram account is connected and so incoming Instagram webhooks are routed to the right
business inbox.
**Response to previous review:** Video 2 shows the Meta login granting Instagram access and
the Instagram account (username + picture) appearing on OT1-Pro's Connections screen.

## instagram_manage_messages
Used to receive Instagram Direct messages sent to the business's Instagram professional
account (via the `instagram` messaging webhook) and to send the business's replies
(`POST /{page-id}/messages` with the Instagram-scoped recipient id) from the OT1-Pro inbox.
Our customers are independent businesses whose customers are the general public, so the
app must work for people who have no role on our app.
**Response to previous review:** Video 2 shows (1) asset selection with the Page and its
Instagram account visible, (2) an incoming Instagram DM sent from a phone appearing in the
OT1-Pro inbox, (3) a live reply sent from the OT1-Pro inbox, and (4) the same reply delivered
in the Instagram app on the phone.

## instagram_business_basic  *(Instagram API with Instagram Login)*
Used with Instagram Business Login (for businesses that connect Instagram directly, without
a Facebook Page) to read the account's user id, username and profile picture so it is
labelled on the Connections screen and incoming webhooks are routed to it.
**Response to previous review:** Video 3 shows the complete Instagram Business Login flow,
the permission grant screen, and the connected account appearing in OT1-Pro.

## instagram_business_manage_messages  *(Instagram API with Instagram Login)*
Used to subscribe the connected account to the `messages` webhook
(`POST /{ig-user-id}/subscribed_apps`), receive its customers' Instagram DMs in the OT1-Pro
inbox, and send the business's replies (`POST /me/messages`).
**Response to previous review:** Video 3 shows (1) account selection/login in the
Instagram Business Login window with the account visible, (2) a live send from the OT1-Pro
inbox, and (3) the same message delivered in the Instagram app on a phone.

## instagram_manage_comments  *(optional — Video 4)*
Businesses can enable "Comment AI" per account (AI Configuration → Comments). When a
customer comments on the business's Instagram post, Meta sends a `comments` webhook; we
generate a reply in the business's configured tone and post it with
`POST /{ig-comment-id}/replies`. We do not hide, delete or moderate comments.
**Response to previous review:** Video 4 shows the login grant, enabling Comment AI, a
comment posted from a phone, and the reply appearing under that comment in the Instagram
app.

## pages_utility_messaging — **do not request** (no template feature exists).
