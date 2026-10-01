# 05 — Runbook for a Claude browser session (Claude in Chrome) or Omar

Paste the prompt below into a Claude Code session on your own computer with the Chrome
extension, logged into developers.facebook.com as an admin of app `1469090344742803`.

```
Fill (do NOT submit) the App Review request for Meta app 1469090344742803 using the files in
docs/meta-app-review/2026-10-resubmission/ of the one-inbox repo. The developer console UI is
in Arabic. For each permission listed in README.md "Scope of this submission":
1. Open developers.facebook.com/apps/1469090344742803/app-review/permissions/ (or the open
   submission under الطلبات), find the permission, click "طلب" / "تعديل".
2. In "أخبرنا عن سبب طلبك…" paste that permission's block from 01-permission-usage.md (English).
3. Upload the video named in 02-screencast-scripts.md for that permission from the folder I tell you.
4. Tick the agreement checkbox ("في حالة الموافقة، أوافق…").
5. If the page says "مطلوب 0 من 1 من عمليات استدعاء واجهة API", stop and tell me — the
   permission needs a successful API test call first (Testing tab), can take 24h to register.
Then fill: App access instructions from 03-reviewer-instructions.md (credentials I will type
myself), and the data-handling questions from 04-data-handling.md.
Remove pages_utility_messaging and pages_manage_engagement from the request if present.
Stop before the final submit button and show me a summary of every field.
```

## Arabic UI ↔ meaning

| Arabic label | Meaning |
|---|---|
| الطلبات | Submissions |
| مراجعة التطبيق | App Review |
| الأذونات والميزات | Permissions and features |
| أخبرنا عن سبب طلبك لميزة … | "Tell us why you're requesting …" (usage text box) |
| قم بتحميل تسجيل شاشة | Upload screen recording |
| مطلوب 0 من 1 من عمليات استدعاء واجهة API | 0 of 1 required API test calls done |
| التعامل مع البيانات | Data handling |
| قم بتوفير تعليمات للوصول إلى التطبيق | App access / test instructions |
| جاهز للاختبار | Ready to test (= Standard Access) |
| وصول متقدم | Advanced Access |

## API test-call requirement

Each requested permission needs ≥1 successful call made by the app in the last 30 days.
The normal app traffic covers messaging permissions; a permission the app never calls (e.g.
`pages_manage_engagement` today) shows 0/1 and blocks submission until a call succeeds.
