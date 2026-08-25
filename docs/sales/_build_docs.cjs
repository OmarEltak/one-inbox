// Generates two .docx files for the marketing + sales team.
// Run: node docs/sales/_build_docs.js
const fs = require('fs');
const path = require('path');
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  Header, Footer, AlignmentType, LevelFormat, HeadingLevel,
  BorderStyle, WidthType, ShadingType, PageNumber,
} = require('docx');

// ---------- shared styling ----------
const FONT = 'Arial';
const DXA_PAGE_W = 12240;   // US Letter
const DXA_PAGE_H = 15840;
const CONTENT_W = 9360;      // 8.5" - 2*1" margins

const border = { style: BorderStyle.SINGLE, size: 4, color: 'CCCCCC' };
const borders = { top: border, bottom: border, left: border, right: border };

function baseStyles() {
  return {
    default: { document: { run: { font: FONT, size: 22 } } }, // 11pt
    paragraphStyles: [
      { id: 'Title', name: 'Title', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 48, bold: true, font: FONT, color: '1F2937' },
        paragraph: { spacing: { before: 0, after: 120 }, outlineLevel: 0 } },
      { id: 'Subtitle', name: 'Subtitle', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 24, italics: true, font: FONT, color: '6B7280' },
        paragraph: { spacing: { before: 0, after: 480 } } },
      { id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 32, bold: true, font: FONT, color: '111827' },
        paragraph: { spacing: { before: 360, after: 160 }, outlineLevel: 0 } },
      { id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 26, bold: true, font: FONT, color: '374151' },
        paragraph: { spacing: { before: 280, after: 120 }, outlineLevel: 1 } },
      { id: 'Heading3', name: 'Heading 3', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 22, bold: true, font: FONT, color: '4B5563' },
        paragraph: { spacing: { before: 200, after: 80 }, outlineLevel: 2 } },
    ],
  };
}

function numbering() {
  return {
    config: [
      { reference: 'bullets', levels: [
        { level: 0, format: LevelFormat.BULLET, text: '•', alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } },
        { level: 1, format: LevelFormat.BULLET, text: '◦', alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 1440, hanging: 360 } } } },
      ]},
      { reference: 'numbers', levels: [
        { level: 0, format: LevelFormat.DECIMAL, text: '%1.', alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 720, hanging: 360 } } } },
      ]},
    ],
  };
}

function sectionProps() {
  return {
    page: {
      size: { width: DXA_PAGE_W, height: DXA_PAGE_H },
      margin: { top: 1440, right: 1440, bottom: 1440, left: 1440 },
    },
  };
}

function p(text, opts = {}) {
  return new Paragraph({
    ...opts,
    children: [new TextRun({ text, ...opts.run })],
  });
}

function h1(text) { return new Paragraph({ heading: HeadingLevel.HEADING_1, children: [new TextRun(text)] }); }
function h2(text) { return new Paragraph({ heading: HeadingLevel.HEADING_2, children: [new TextRun(text)] }); }
function h3(text) { return new Paragraph({ heading: HeadingLevel.HEADING_3, children: [new TextRun(text)] }); }

function bullet(text, level = 0) {
  return new Paragraph({
    numbering: { reference: 'bullets', level },
    children: parseInline(text),
  });
}

// Support **bold** in inline text.
function parseInline(text) {
  const parts = String(text).split(/(\*\*[^*]+\*\*)/g).filter(Boolean);
  return parts.map(part => {
    if (part.startsWith('**') && part.endsWith('**')) {
      return new TextRun({ text: part.slice(2, -2), bold: true });
    }
    return new TextRun({ text: part });
  });
}

function para(text) {
  return new Paragraph({
    children: parseInline(text),
    spacing: { after: 120 },
  });
}

function calloutBox(label, text, fill = 'FEF3C7') {
  const cell = new TableCell({
    borders,
    width: { size: CONTENT_W, type: WidthType.DXA },
    shading: { fill, type: ShadingType.CLEAR },
    margins: { top: 160, bottom: 160, left: 200, right: 200 },
    children: [
      new Paragraph({ children: [new TextRun({ text: label, bold: true, size: 22 })], spacing: { after: 80 } }),
      new Paragraph({ children: parseInline(text) }),
    ],
  });
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA },
    columnWidths: [CONTENT_W],
    rows: [new TableRow({ children: [cell] })],
  });
}

function twoColTable(headers, rows, widths = [3120, 6240]) {
  const headerCell = (text, w) => new TableCell({
    borders,
    width: { size: w, type: WidthType.DXA },
    shading: { fill: '1F2937', type: ShadingType.CLEAR },
    margins: { top: 100, bottom: 100, left: 140, right: 140 },
    children: [new Paragraph({ children: [new TextRun({ text, bold: true, color: 'FFFFFF' })] })],
  });
  const bodyCell = (text, w) => new TableCell({
    borders,
    width: { size: w, type: WidthType.DXA },
    margins: { top: 100, bottom: 100, left: 140, right: 140 },
    children: [new Paragraph({ children: parseInline(text) })],
  });
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA },
    columnWidths: widths,
    rows: [
      new TableRow({ tableHeader: true, children: headers.map((t, i) => headerCell(t, widths[i])) }),
      ...rows.map(r => new TableRow({ children: r.map((t, i) => bodyCell(t, widths[i])) })),
    ],
  });
}

function pageBreak() { return new Paragraph({ children: [], pageBreakBefore: true }); }

function makeFooter(title) {
  return new Footer({
    children: [new Paragraph({
      alignment: AlignmentType.RIGHT,
      children: [
        new TextRun({ text: `${title}  •  Page `, size: 18, color: '9CA3AF' }),
        new TextRun({ children: [PageNumber.CURRENT], size: 18, color: '9CA3AF' }),
        new TextRun({ text: ' of ', size: 18, color: '9CA3AF' }),
        new TextRun({ children: [PageNumber.TOTAL_PAGES], size: 18, color: '9CA3AF' }),
      ],
    })],
  });
}

// ======================================================================
// DOC 1: FEATURE GUIDE (marketing + sales)
// ======================================================================
function buildFeatureGuide() {
  const children = [];
  children.push(new Paragraph({ style: 'Title', children: [new TextRun('OT1-Pro — Product Feature Guide')] }));
  children.push(new Paragraph({ style: 'Subtitle', children: [new TextRun('For the marketing and sales team. What the product does, who it is for, and how to talk about each feature in the customer dashboard.')] }));

  // Intro
  children.push(h1('1. What is OT1-Pro?'));
  children.push(para('OT1-Pro is a **multi-tenant social inbox with a built-in AI sales responder**. It unifies conversations from Facebook, Instagram, WhatsApp, Telegram, and Email into a single inbox, and lets an AI answer customers in the brand’s voice — in Arabic or English — 24/7, even while the human team sleeps.'));
  children.push(para('The core promise is simple: **stop losing sales because you replied 6 hours late on Instagram at 2 AM.** OT1-Pro replies instantly, qualifies the lead, and hands off to a human only when the deal is close.'));

  children.push(h2('Who it is for'));
  children.push(bullet('**Small businesses running sales through DMs** — Instagram shops, Facebook Page-based storefronts, WhatsApp-first businesses. This is 90% of SMB commerce in MENA.'));
  children.push(bullet('**Marketing agencies** running ads that dump leads into DMs. They need the DM funnel to actually convert, not sit unanswered.'));
  children.push(bullet('**Solo founders and small teams** who cannot afford a full-time BDR but are losing leads to slow replies.'));

  children.push(h2('What makes it different from generic inboxes'));
  children.push(bullet('**AI is the default, not an add-on.** Competitors like WATI ship an inbox and charge extra for AI. Here, the AI ships with the product and speaks Arabic natively.'));
  children.push(bullet('**Managed onboarding.** For customers who cannot navigate Meta’s OAuth (99% of SMBs in MENA), our team connects the pages for them. See "Connections" below.'));
  children.push(bullet('**One workspace, all channels.** Facebook, Instagram, WhatsApp, Telegram, and Email side by side. No per-channel license.'));

  children.push(pageBreak());

  // ============ SIDEBAR FEATURES ============
  children.push(h1('2. The Dashboard, feature by feature'));
  children.push(para('Every logged-in customer sees a left sidebar with these items. This section walks each one, in the order it appears.'));

  // Workspace switcher
  children.push(h2('2.1  Workspace chip (top of sidebar)'));
  children.push(para('At the very top of the sidebar, each customer sees their **workspace name and current plan badge** (Free / Starter / Pro / Enterprise). If they belong to more than one workspace (e.g. an agency managing several client accounts), a dropdown lets them switch between them without logging out.'));
  children.push(calloutBox('Sales angle', 'Highlight for agencies: "One login, unlimited client workspaces. You bill each client separately, but you work in one tab." This is the exact objection agencies raise against WATI.'));

  // Home
  children.push(h2('2.2  Home'));
  children.push(para('The dashboard landing page. Shows a **welcome tile, quick-connect shortcuts, message and conversation counts, AI usage this month, and a pending-action list** (unread messages, expiring page tokens, etc.).'));
  children.push(bullet('If the workspace has **no connections yet**, Home turns into an onboarding wizard pushing the user toward the Connections page.'));
  children.push(bullet('If AI is disabled or over quota, Home shows a banner explaining why replies are paused.'));

  // Inbox
  children.push(h2('2.3  Inbox'));
  children.push(para('The unified message inbox. This is where sales agents and business owners actually spend their day.'));
  children.push(h3('What it does'));
  children.push(bullet('**Merges conversations from every connected platform** into one list. Facebook Messenger, Instagram DMs, WhatsApp chats, Telegram bots, and Email threads all appear as normal-looking conversations.'));
  children.push(bullet('**Per-page filtering.** Under "Inbox" in the sidebar, customers see a dropdown of their connected pages, each color-coded by platform (blue for Facebook, pink for Instagram, green for WhatsApp, orange for Email, cyan for Telegram). Click one to filter the inbox to just that page.'));
  children.push(bullet('**Real-time updates.** Uses Laravel Reverb websockets. When a new message arrives, the inbox updates instantly and the browser sends a desktop notification if the tab is in the background.'));
  children.push(bullet('**Unread badge** on the sidebar Inbox item, showing pending messages across all pages (caps at "99+").'));
  children.push(bullet('**AI reply drafts** appear inline. The agent can accept, edit, or override before sending.'));
  children.push(bullet('**Spam and reactivation.** The AI can flag junk conversations. A human can reactivate a flagged conversation with one click, and the system will not re-flag it (this was a real bug we fixed).'));

  children.push(h3('Talking points'));
  children.push(bullet('"You will never again miss a Direct Message because it landed in the wrong app." That is the entire pitch in one line.'));
  children.push(bullet('"Your agents do not need to install the Facebook Business Suite, Meta Business Suite, WhatsApp Web, and the Telegram app. They open one tab."'));

  // Contacts
  children.push(h2('2.4  Contacts'));
  children.push(para('A CRM-style view of every person who has ever messaged the business, across every connected platform. Each contact has: name, avatar, platform, last-seen date, tags, notes, conversation history, and a flag for whether they are a lead, a customer, or spam.'));
  children.push(bullet('**Search and filter** by name, platform, tag, or activity date.'));
  children.push(bullet('**Import** contacts from a CSV or spreadsheet (see the ContactImporter service).'));
  children.push(bullet('**Merge** contacts — useful when the same person messages on Instagram and WhatsApp under different names.'));
  children.push(bullet('**Tag customers** manually (e.g. "hot lead", "paid customer", "abandoned cart") to drive campaign segmentation later.'));

  // Campaigns
  children.push(h2('2.5  Campaigns'));
  children.push(para('Outbound broadcast messaging. The customer builds a message once and sends it to a **segment of their contacts** (all WhatsApp customers, all Instagram followers who bought last month, etc.).'));
  children.push(bullet('**Template renderer** with variables like `{{name}}` and `{{last_product}}`, so each recipient gets a personalized version.'));
  children.push(bullet('**Scheduled sending** with a dispatcher that paces messages to stay under platform rate limits.'));
  children.push(bullet('**Delivery, open, and reply tracking** where the platform reports it (WhatsApp Cloud reports deliveries, Instagram does not).'));
  children.push(calloutBox('Sales angle', 'Campaigns are how customers **turn their inbox into a marketing channel**. "You already have 4,000 people who messaged you. Do you send them anything? No? OK, that is a campaign you already own — you just have not shipped it."'));

  // Analytics
  children.push(h2('2.6  Analytics'));
  children.push(para('Charts and KPIs for the workspace:'));
  children.push(bullet('**Volume** — messages received / sent per day, per platform, per page.'));
  children.push(bullet('**Response time** — average first-response time, human vs AI.'));
  children.push(bullet('**AI performance** — how many conversations the AI handled without human touch, and how many were handed off.'));
  children.push(bullet('**Sales funnel** — conversations → qualified leads → closed deals (using tags applied in Contacts).'));

  // AI Chat
  children.push(h2('2.7  AI Chat'));
  children.push(para('An **internal ChatGPT-style assistant** scoped to the customer’s own business data. The customer can ask questions like "How many leads did we get from Facebook this week?" or "Draft a WhatsApp campaign for our new perfume launch aimed at customers who bought before Ramadan," and the AI answers using the workspace’s real data.'));
  children.push(bullet('Multi-turn conversation, saved history.'));
  children.push(bullet('Can generate campaign copy, draft replies to specific customers, summarize a week’s inbox.'));

  // AI Settings
  children.push(h2('2.8  AI Settings'));
  children.push(para('Where the customer **teaches the AI who their business is**. This is the highest-leverage screen in the app.'));
  children.push(bullet('**Business description** — what the business does, its tone, its values.'));
  children.push(bullet('**Product / service catalog** with prices — the AI uses this to answer "how much is X?" correctly.'));
  children.push(bullet('**FAQ knowledge base** — typical customer questions and the approved answers.'));
  children.push(bullet('**Escalation rules** — when to hand off to a human (e.g. "always hand off if the customer asks about refunds").'));
  children.push(bullet('**AI ON / OFF toggle** — visible as a green or red dot next to the AI Settings item in the sidebar. Green = AI is answering. Red = only humans reply.'));
  children.push(bullet('**Language selection** — Arabic (default in MENA), English, or bilingual with automatic language detection per contact.'));
  children.push(calloutBox('Sales angle', 'This is where you win the demo. Load a real business (say, a Cairo perfumery) into AI Settings live on the call. Send it a test message on Instagram. Watch the AI answer in Egyptian Arabic, in the shop’s voice, with the correct price, in under 5 seconds. That is the moment the prospect decides.'));

  // Connections
  children.push(h2('2.9  Connections'));
  children.push(para('Where the customer wires up their social accounts. This is the **gate to the entire product** — until at least one page is connected, Inbox / Contacts / Campaigns / Analytics / AI are all grayed out with a padlock icon.'));
  children.push(h3('Supported channels'));
  children.push(twoColTable(
    ['Channel', 'How it connects'],
    [
      ['Facebook Pages', 'Meta OAuth (customer’s Facebook account approves the Page) OR managed onboarding by our team.'],
      ['Instagram (Business)', 'Same Meta OAuth flow — Instagram must be linked to a Facebook Page.'],
      ['WhatsApp (QR)', 'Scan a QR code with the WhatsApp phone. Uses our self-hosted Evolution API gateway. No Meta approval required.'],
      ['WhatsApp Cloud API', 'Official Meta WhatsApp Business API. Requires WhatsApp Business Account setup.'],
      ['Telegram', 'Customer creates a bot via @BotFather in Telegram, pastes the bot token into OT1-Pro. Takes 90 seconds.'],
      ['Email (IMAP + SMTP)', 'Enter mailbox credentials or use Gmail / Outlook OAuth. Scheduler polls every 2 minutes.'],
      ['Also wired', 'TikTok, Snapchat, Discord, Slack, Web Chat widget (varying levels of completeness).'],
    ],
  ));
  children.push(para('Each connected account becomes a "Page" inside the workspace. Multiple pages per platform are allowed (e.g. an agency can add 5 client Facebook Pages under one workspace).'));

  // Settings (admins)
  children.push(h2('2.10  Settings (admin management)'));
  children.push(para('Only visible to workspace owners and head admins. Manages:'));
  children.push(bullet('**Team members** — invite agents, assign roles (Head Admin, Admin, Agent).'));
  children.push(bullet('**Per-user permissions** — granular flags for who can see Inbox, Contacts, Campaigns, Analytics, AI, and Connections.'));
  children.push(bullet('**Workspace preferences** — timezone, default language, notification rules.'));

  // Super admin (for internal reference, marketing still asks about it)
  children.push(h2('2.11  Super-admin section (internal team only)'));
  children.push(para('Not visible to normal customers. Only our internal team sees these items. Included here so marketing understands the full product surface when talking to enterprise buyers who ask "who supports us?"'));
  children.push(bullet('**Customers** — list every workspace, their plan, MRR, connection count.'));
  children.push(bullet('**Subscriptions** — manage plan changes, extensions, trials.'));
  children.push(bullet('**Page Assignments** — audit which workspace owns which Page.'));
  children.push(bullet('**Onboarding Requests** — the queue where customer "connect for me" requests appear. Super-admin OAuths on their behalf and re-assigns the Page to the customer team. This is the workaround for Meta App Review (see Objection Handling document).'));
  children.push(bullet('**Blog** — CMS for the SEO blog on ot1-pro.com (this is how customers find us on Google).'));

  children.push(pageBreak());

  // ============ HEADER + GLOBAL FEATURES ============
  children.push(h1('3. Global features (always available)'));

  children.push(h2('3.1  Notifications'));
  children.push(bullet('**In-app real-time updates** via Laravel Reverb websockets — new messages appear instantly without refreshing.'));
  children.push(bullet('**Browser desktop notifications** when the app tab is in the background.'));
  children.push(bullet('**Bell icon in the header** for future notification history (currently minimal).'));

  children.push(h2('3.2  Team / workspace switcher'));
  children.push(para('Users who belong to more than one workspace switch between them from the sidebar chip. Session-scoped, no re-login. Agencies rely on this.'));

  children.push(h2('3.3  Bilingual (Arabic and English) with RTL'));
  children.push(para('Full RTL layout in Arabic. Every string is translated. The AI itself speaks Egyptian, Levantine, or Modern Standard Arabic depending on the customer’s locale setting and the incoming message.'));

  children.push(h2('3.4  Plans'));
  children.push(twoColTable(
    ['Plan', 'Positioning'],
    [
      ['Free', 'Try the product. Limited AI quota per month. One page. No campaigns.'],
      ['Starter (~$29/mo)', 'Solo founders and micro businesses. All platforms, up to a few thousand AI replies.'],
      ['Pro (~$79/mo)', 'Small teams. Higher AI quota, campaigns unlocked, team members, analytics.'],
      ['Enterprise (Custom)', 'Agencies, larger teams. Unlimited pages, priority AI models, dedicated support, managed onboarding included.'],
    ],
  ));
  children.push(para('**Sales is currently handled manually via WhatsApp on +20 10 26361218.** Prospects who click "Upgrade" are pointed to WhatsApp. No self-serve billing yet.'));

  children.push(pageBreak());

  // ============ QUICK PITCH ============
  children.push(h1('4. The 30-second pitch (memorize this)'));
  children.push(calloutBox(
    'The pitch',
    '"OT1-Pro is a unified inbox for Facebook, Instagram, WhatsApp, Telegram, and Email with an Arabic-speaking AI sales agent built in. Your customer messages you at 2 AM on Instagram — our AI replies in your brand’s voice, in Arabic, with the correct product price, in under 5 seconds. When the conversation gets close to a sale, it hands off to your human team. You stop losing DMs. You stop paying for a night-shift agent. You start closing leads you did not even know you had."',
    'DBEAFE',
  ));

  children.push(h1('5. Top objections — quick answers'));
  children.push(para('For the detailed version see the companion **Objection Handling Playbook**.'));
  children.push(twoColTable(
    ['Objection', 'One-line answer'],
    [
      ['"WATI already does this."', 'WATI is WhatsApp-only and English-first. We are multi-channel and Arabic-native, at half the price.'],
      ['"I already have Meta Business Suite."', 'Meta Business Suite has no AI, no CRM, no cross-platform contacts, no campaigns. It is an inbox viewer.'],
      ['"AI cannot handle my customers."', 'You train it in AI Settings with your products, prices, and FAQs. And the toggle turns it off in one click if you disagree.'],
      ['"How do I connect my Facebook Page?"', 'Two ways: OAuth if you can, or we do it for you via managed onboarding. See Objection Handling doc for details.'],
      ['"Is WhatsApp safe? Will my number get banned?"', 'For QR connection: risk exists, mitigated. For WhatsApp Cloud API: officially Meta-approved, zero ban risk. Recommend Cloud API to serious customers.'],
    ],
  ));

  return new Document({
    styles: baseStyles(),
    numbering: numbering(),
    sections: [{
      properties: sectionProps(),
      footers: { default: makeFooter('OT1-Pro Feature Guide') },
      children,
    }],
  });
}

// ======================================================================
// DOC 2: OBJECTION HANDLING PLAYBOOK (limitations, honest)
// ======================================================================
function buildObjectionPlaybook() {
  const children = [];
  children.push(new Paragraph({ style: 'Title', children: [new TextRun('OT1-Pro — Objection Handling Playbook')] }));
  children.push(new Paragraph({ style: 'Subtitle', children: [new TextRun('Internal reference for the sales team. Where the product has real limitations, what the workaround is, and exactly what to say when a prospect pushes back.')] }));

  children.push(calloutBox(
    'Read this first',
    '**These are not secrets to hide. They are talking points to own.** Every SaaS has limitations — what separates good sales from bad sales is knowing them cold. When a prospect asks "why does X not work," a rehearsed, calm, "yes, here is the reason and here is how we solve it for you" beats stumbling every time. If you catch yourself feeling defensive, you have not read this document enough.',
    'FEF3C7',
  ));

  // ============================================================
  children.push(h1('1. Meta App Review — the big one'));

  children.push(h2('What the limitation is'));
  children.push(para('Our Meta app (App ID `1469090344742803`) is **not yet fully approved for Advanced Access** on all Facebook and Instagram permissions. Meta requires two separate approvals:'));
  children.push(bullet('(a) **Business Portfolio Verification** — done ✅ (verified 2026-07-21 for OT1 Pro business ID `2169075923895403`).'));
  children.push(bullet('(b) **App Review with Advanced Access on each permission** — in progress. Until each permission shows "Advanced Access" (not "جاهز للاختبار" / "Ready to Test"), any non-admin Facebook user who tries our OAuth flow gets the error **"Feature unavailable: Facebook Login is currently unavailable for this app."**'));

  children.push(h2('What this affects'));
  children.push(bullet('The direct "Connect Facebook Page" and "Connect Instagram" buttons in the Connections page cannot be used by regular customers today.'));
  children.push(bullet('This is why the sidebar’s Inbox stays locked for customers who cannot connect — they need a Page connected first.'));

  children.push(h2('The workaround (this is actually a feature)'));
  children.push(para('We built **Managed Onboarding**. When a customer clicks Connect on Facebook or Instagram, they see "Request connection" instead of the raw OAuth. What happens:'));
  children.push(new Paragraph({
    numbering: { reference: 'numbers', level: 0 },
    children: parseInline('The customer submits a request with their Page name and grants us admin access on their Business Manager.'),
  }));
  children.push(new Paragraph({
    numbering: { reference: 'numbers', level: 0 },
    children: parseInline('Our super-admin OAuths into their Page **through our own OT1-Pro account** (which is a Meta app admin, so the "Feature unavailable" error does not apply to us).'),
  }));
  children.push(new Paragraph({
    numbering: { reference: 'numbers', level: 0 },
    children: parseInline('The Page is then **transferred to the customer’s workspace** via the Onboarding Requests screen. The customer sees the Page appear in their sidebar, ready to receive messages.'),
  }));
  children.push(new Paragraph({
    numbering: { reference: 'numbers', level: 0 },
    children: parseInline('Total time from request to live: typically **under 10 minutes during business hours**.'),
  }));

  children.push(h2('What to say to the prospect'));
  children.push(calloutBox(
    'Script',
    '**Prospect:** "I clicked Connect Facebook and got an error."\n\n**You:** "That is expected — we run a **white-glove connection service** for Facebook and Instagram. Most business owners get lost in Meta’s Business Manager screens anyway, so we do it for you. Send us your Page name on WhatsApp, add our admin, and we will have you receiving messages inside 10 minutes. Zero setup pain on your side. Once Meta finishes reviewing our app (it is a normal review cycle for every SaaS), the self-serve button will work too."',
    'DBEAFE',
  ));

  children.push(h2('Timeline for full approval'));
  children.push(bullet('Business portfolio: done.'));
  children.push(bullet('Per-permission Advanced Access: submitted, in Meta’s review queue. **Do not promise a date to prospects.** Meta reviews are opaque and often take 2 to 6 weeks per permission.'));
  children.push(bullet('When it is done, we will flip a single env var (`META_APP_VERIFIED=true`) and the direct OAuth button becomes available. No product change is needed.'));

  children.push(pageBreak());

  // ============================================================
  children.push(h1('2. WhatsApp — QR vs Cloud API'));

  children.push(h2('The limitation'));
  children.push(para('WhatsApp has **two ways to connect**, and each has trade-offs. Sales must understand both.'));

  children.push(twoColTable(
    ['Method', 'Trade-off'],
    [
      ['**QR connection** (via our self-hosted Evolution API gateway)', 'Free, instant, no Meta approval. But it uses **unofficial WhatsApp Web protocol** — there is a non-zero risk WhatsApp bans the number, especially if the customer sends bulk messages or gets reported as spam. Suitable for organic inbound conversations, not for aggressive outbound campaigns.'],
      ['**WhatsApp Cloud API** (official Meta API)', 'Zero ban risk, official Meta support, higher rate limits, template messaging. But the customer must create a WhatsApp Business Account through Meta, get a phone number approved, and pay Meta’s per-conversation fees (roughly $0.005 to $0.09 depending on country and conversation type).'],
    ],
  ));

  children.push(h2('What to say'));
  children.push(bullet('**For small shops, solo founders, and inbound-only use cases:** recommend QR. "Scan the code with your WhatsApp phone. Done in 30 seconds. Costs you nothing extra."'));
  children.push(bullet('**For businesses sending campaigns to hundreds of contacts or larger organizations:** recommend Cloud API. "It is the safe long-term path. Meta charges a small per-conversation fee, but your number cannot be banned and you get delivery guarantees."'));

  children.push(h2('If they push back on QR ban risk'));
  children.push(calloutBox(
    'Script',
    '**Prospect:** "I heard WhatsApp bans numbers using unofficial gateways."\n\n**You:** "You are right to ask. It happens when businesses use these gateways to blast cold outbound at strangers. If you are using it to reply to people who already messaged you, the ban risk is very low — those are the exact conversations WhatsApp wants to happen. Also, our gateway is self-hosted, not shared, so we are not on any of the flagged provider lists. And if you ever want to graduate to the official Cloud API, we support that too — same inbox, same AI, just a different connection under the hood."',
    'DBEAFE',
  ));

  children.push(pageBreak());

  // ============================================================
  children.push(h1('3. Instagram — same Meta gate as Facebook'));

  children.push(para('Instagram connects **through Meta’s Graph API** (the same OAuth as Facebook), so it has the exact same Advanced Access limitation described in Section 1.'));
  children.push(bullet('The workaround is the same: managed onboarding by our team.'));
  children.push(bullet('One additional wrinkle: the customer’s Instagram must be a **Business or Creator account** and it must be **linked to a Facebook Page** in the Meta Business Suite. If it is a personal Instagram account, no software (not even Meta’s own) can connect to it. This is a Meta rule, not ours.'));

  children.push(h2('What to say'));
  children.push(calloutBox(
    'Script',
    '**Prospect:** "My Instagram will not connect."\n\n**You:** "Two things to check. First, is your Instagram set to Business or Creator (not Personal)? You can switch it in Instagram settings in one tap — no data is lost. Second, is it linked to your Facebook Page? If both yes, send it to us on WhatsApp and we will handle the connection for you inside 10 minutes."',
    'DBEAFE',
  ));

  children.push(pageBreak());

  // ============================================================
  children.push(h1('4. Email — polling, not push'));

  children.push(h2('The limitation'));
  children.push(para('Email arrives in our inbox by **polling the customer’s mailbox every 2 minutes** via the Laravel scheduler. Unlike Facebook or WhatsApp (which push messages to us in real time via webhooks), IMAP does not support real-time push in the standard way social platforms do.'));

  children.push(h2('What this means in practice'));
  children.push(bullet('A customer email might take **up to 2 minutes to appear in the inbox**, not instantly.'));
  children.push(bullet('The AI reply is still fast (a few seconds) once the email lands, so the total worst case is roughly 2 minutes.'));
  children.push(bullet('Not a real issue for email as a channel — customers expect email to have some delay. Only worth mentioning if the prospect asks directly.'));

  children.push(h2('What to say'));
  children.push(bullet('Only bring this up if asked. If asked: "Email is not real-time push — no email service is, in the way social DMs are. We check the mailbox every 2 minutes, so worst case a reply goes out 2 minutes and 5 seconds after the customer emails. In practice nobody notices."'));

  children.push(pageBreak());

  // ============================================================
  children.push(h1('5. Telegram — requires customer to make a bot'));

  children.push(para('Telegram’s API model is different: **there is no OAuth**. To connect Telegram, the customer opens Telegram, talks to **@BotFather**, creates a bot in about 90 seconds, and pastes the bot token into OT1-Pro.'));
  children.push(bullet('This is a two-minute one-time task, but it is a task, not a click. Some non-technical customers stall here.'));
  children.push(bullet('Once connected, customers message the bot (not a personal account). This is standard Telegram business practice — every serious Telegram business uses a bot.'));

  children.push(h2('What to say'));
  children.push(calloutBox(
    'Script',
    '**Prospect:** "How do I connect Telegram?"\n\n**You:** "Telegram is 90 seconds. Open Telegram, search for @BotFather, type /newbot, give it a name, paste the token it gives you into our Connections screen. Done. If you want, we can screen-share and walk you through it — takes literally two minutes."',
    'DBEAFE',
  ));

  children.push(pageBreak());

  // ============================================================
  children.push(h1('6. TikTok, Snapchat, Discord, Slack, Web Chat'));
  children.push(para('These platforms are **wired in the codebase but at varying levels of completeness**. TikTok and Snapchat message APIs are heavily restricted by the platforms themselves (both are hostile to third-party message clients), so functionality is limited.'));

  children.push(h2('What to say'));
  children.push(bullet('If the prospect asks: "TikTok and Snapchat are on our roadmap and technically wired — the platforms themselves are the bottleneck. We ship features as soon as they open up their APIs. For today, focus on the channels where you actually have volume: Facebook, Instagram, WhatsApp, Telegram, Email."'));
  children.push(bullet('**Never oversell TikTok / Snapchat as fully working today.** That is a promise the platform, not us, controls.'));

  children.push(pageBreak());

  // ============================================================
  children.push(h1('7. No self-serve billing yet'));

  children.push(h2('The limitation'));
  children.push(para('There is **no Stripe checkout, no credit-card form, no automatic plan upgrade**. When a customer clicks "Upgrade" on any plan, they are directed to WhatsApp us on **+20 10 26361218** to talk to sales.'));

  children.push(h2('Why this is actually fine for now'));
  children.push(bullet('Every serious sale in this market happens on WhatsApp anyway. Nobody in MENA SMB buys software from a Stripe form without a human conversation first.'));
  children.push(bullet('It gives sales a chance to **upsell to a higher plan** and to **learn about the customer’s use case** before they pay.'));
  children.push(bullet('It filters out prospects who are not serious — anyone who cannot be bothered to send one WhatsApp message was never going to be a paying customer.'));

  children.push(h2('What to say'));
  children.push(bullet('If the prospect asks about billing: "Right now we onboard every paying customer personally on WhatsApp so we can make sure your setup is right on day one. It takes 10 minutes. Self-serve card checkout is coming later this year, but frankly the personal onboarding is why our activation rate is so high."'));

  children.push(pageBreak());

  // ============================================================
  children.push(h1('8. AI — quotas, failover, and the "I apologize" trap'));

  children.push(h2('What to know'));
  children.push(bullet('The AI has a **monthly reply quota per plan**. When quota is exhausted, AI replies stop and the customer sees a banner in Home + a red dot next to AI Settings in the sidebar. **Human replies still work.**'));
  children.push(bullet('The AI routes across multiple providers (via our own router: Anthropic Claude, Google Gemini, others). If one provider is down, we failover automatically — the customer never sees an outage.'));
  children.push(bullet('**Silent failure is by design.** If the AI cannot produce a reply, it sends **nothing** rather than an "I apologize, I am having trouble" message. We had a real production bug where customers thought the bot’s apology was a real reply and thought their business was insulting them. Never again.'));

  children.push(h2('What to say if AI runs out mid-month'));
  children.push(calloutBox(
    'Script',
    '**Prospect:** "What if I run out of AI replies mid-month?"\n\n**You:** "Two options: upgrade to a higher plan (takes 30 seconds on WhatsApp), or wait until the next billing cycle — your human agents can still reply through the same inbox. Nothing breaks. And based on real usage, 90% of customers on Starter never actually hit the Starter quota."',
    'DBEAFE',
  ));

  children.push(pageBreak());

  // ============================================================
  children.push(h1('9. Cheat sheet — what to lead with, what to defer'));

  children.push(twoColTable(
    ['Lead with', 'Only mention if asked'],
    [
      ['Unified inbox across Facebook, Instagram, WhatsApp, Telegram, Email', 'The exact Meta App Review status'],
      ['Arabic-native AI that answers 24/7 in the brand’s voice', 'That WhatsApp QR is unofficial'],
      ['Managed onboarding — we connect it for you', 'Email polling latency (~2 min)'],
      ['Multi-workspace support for agencies', 'That there is no self-serve billing'],
      ['Live in 10 minutes, not 3 weeks like WATI', 'TikTok / Snapchat completeness'],
      ['One tab, one team, one price', 'AI monthly quota specifics until they ask'],
    ],
  ));

  children.push(h1('10. Golden rule'));
  children.push(calloutBox(
    'Remember',
    '**Every limitation in this document has a workaround, and every workaround is either automatic or handled by us in under 10 minutes.** The prospect does not need to know the internal mechanics — they need to know that if they run into a wall, a human will get them past it fast. That is not a weakness — that is a promise we can keep, and WATI cannot.',
    'DCFCE7',
  ));

  return new Document({
    styles: baseStyles(),
    numbering: numbering(),
    sections: [{
      properties: sectionProps(),
      footers: { default: makeFooter('OT1-Pro Objection Handling Playbook') },
      children,
    }],
  });
}

// ======================================================================
// WRITE
// ======================================================================
async function main() {
  const outDir = path.resolve(__dirname);
  const guide = buildFeatureGuide();
  const playbook = buildObjectionPlaybook();

  const guideBuf = await Packer.toBuffer(guide);
  fs.writeFileSync(path.join(outDir, 'OT1-Pro_Feature_Guide.docx'), guideBuf);

  const playbookBuf = await Packer.toBuffer(playbook);
  fs.writeFileSync(path.join(outDir, 'OT1-Pro_Objection_Handling_Playbook.docx'), playbookBuf);

  console.log('Wrote:');
  console.log('  ' + path.join(outDir, 'OT1-Pro_Feature_Guide.docx'));
  console.log('  ' + path.join(outDir, 'OT1-Pro_Objection_Handling_Playbook.docx'));
}

main().catch(e => { console.error(e); process.exit(1); });
