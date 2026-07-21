<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Programmatic vertical landing pages — one URL per role/team type.
 *
 * URL pattern: /unified-inbox-for-{role}
 *
 * Each entry in ::ROLES generates a full landing page targeting the literal
 * search phrase "unified inbox for [role]" — validated by Search Console
 * data showing 22+ impressions/day for "unified inbox for engineering
 * managers" alone (July 2026).
 */
class VerticalLandingController extends Controller
{
    /**
     * All supported roles. Add new entries here to launch new landing pages.
     * The array key becomes the URL slug.
     */
    private const ROLES = [
        'engineering-managers' => [
            'role_label'  => 'Engineering Managers',
            'keyword'     => 'Unified Inbox for Engineering Managers',
            'headline'    => 'One Inbox for Every Engineering Communication Channel',
            'subhead'     => 'Slack overload, GitHub notifications, on-call pages, customer bug reports, and stakeholder pings — engineering managers spend 40% of their week context-switching. OT1-Pro consolidates every channel into one inbox with AI triage.',
            'pain_intro'  => 'Engineering managers face a specific version of the multichannel problem:',
            'pain_points' => [
                'Bug reports arrive via Slack, GitHub Issues, Linear, customer emails, and support tickets — all requiring different responses.',
                'On-call pages compete for attention with stakeholder update requests, sprint reviews, and 1:1 prep.',
                'Cross-team escalations get lost when they land in the wrong channel or the wrong person\'s DMs.',
                'Every context switch costs 23 minutes of deep work time (per University of California research).',
            ],
            'metrics' => [
                ['Context switches/day', '−73%', 'from 47 → 12 average'],
                ['Bug triage time', '< 2 min', 'AI classifies severity + owner'],
                ['Response SLA compliance', '98%', 'nothing falls through'],
            ],
            'use_cases' => [
                ['🐛', 'Bug Report Triage', 'AI reads incoming bug reports from Slack, GitHub, and email — assigns severity, tags the right owner, and drafts an initial response.'],
                ['🚨', 'On-Call Escalation', 'PagerDuty, Opsgenie, and customer emergency channels feed into one triage queue with severity scoring.'],
                ['📊', 'Stakeholder Updates', 'Product managers and executives ping across 4+ tools — AI drafts status updates from your team\'s recent activity.'],
                ['🔗', 'Cross-Team Handoffs', 'Design, product, and support requests routed to the right engineer without email chains.'],
                ['📝', 'PR Review Prompts', 'GitHub PR review requests surface in the inbox with context — no more dead PRs waiting 3 days.'],
                ['💬', 'Customer Feedback', 'Feature requests and complaints from customers reach engineering with full conversation context, not summaries.'],
            ],
            'faq' => [
                ['Does this replace Slack for engineering teams?', 'No — OT1-Pro complements Slack by pulling in external channels (customer emails, GitHub, on-call pages) that don\'t live in Slack. Your team keeps using Slack for internal chat.'],
                ['Can I integrate with GitHub, Linear, or Jira?', 'Yes — webhook-based integrations sync issues, PRs, and tickets. New bug reports auto-triage on arrival.'],
                ['How does AI classify bug severity?', 'The AI reads the report content and matches it against your team\'s severity rubric (which you configure). Standard patterns are pre-loaded: production outage, data loss, security, feature request.'],
                ['Will this add noise to my day?', 'The opposite — the whole point is to reduce noise by consolidating and pre-filtering. AI routes low-priority items away from your immediate attention.'],
            ],
        ],

        'sales-teams' => [
            'role_label'  => 'Sales Teams',
            'keyword'     => 'Unified Inbox for Sales Teams',
            'headline'    => 'Close Deals Faster With Every Sales Channel in One Inbox',
            'subhead'     => 'Sales reps chase leads across WhatsApp, Instagram, LinkedIn, email, and phone — losing 40% of prospects to slow first-response time. OT1-Pro unifies every channel with AI qualifying leads before they cool off.',
            'pain_intro'  => 'Sales teams lose deals to messaging chaos:',
            'pain_points' => [
                'Leads reach out on WhatsApp/Instagram DM, expect a reply in minutes, and go cold if it takes hours.',
                'CRM data lives in HubSpot/Salesforce but conversations live everywhere else — reps duplicate work updating both.',
                'Follow-up cadences break when reps forget which channel a lead prefers.',
                'Managers can\'t see rep performance across channels — dashboards show email/calls, not DMs.',
            ],
            'metrics' => [
                ['First-response time', '< 30s', 'from 47 min average'],
                ['Lead-to-close rate', '+38%', 'AI qualifies before rep touches'],
                ['CRM data completeness', '95%', 'auto-logged, not manual'],
            ],
            'use_cases' => [
                ['⚡', 'Instant Lead Response', 'AI answers within 30 seconds on any channel, qualifies the lead, and routes to the right rep.'],
                ['🎯', 'AI Lead Qualification', '2-3 conversational questions score leads BANT-style before a human sees them.'],
                ['📞', 'Multi-Channel Follow-Up', 'AI runs 7-touch cadences across email + WhatsApp + LinkedIn — consistent every time.'],
                ['🔄', 'CRM Auto-Sync', 'Every conversation logged to HubSpot/Salesforce/Pipedrive with full context.'],
                ['🎁', 'Warm Handoff', 'When AI qualifies a hot lead, human closer gets Slack ping + conversation context.'],
                ['📈', 'Cross-Channel Reporting', 'See which channel converts, which rep closes fastest, where the pipeline stalls.'],
            ],
            'faq' => [
                ['Does OT1-Pro replace my CRM?', 'No — it augments it. Every conversation syncs to your existing HubSpot, Salesforce, or Pipedrive automatically.'],
                ['Can AI actually close deals or just qualify?', 'For simple deals (fixed-price, in-stock), AI can close. For complex sales, AI qualifies then hands to a human — increasing rep productivity 3-5x.'],
                ['Which sales channels does it cover?', 'WhatsApp, Instagram DM, Facebook Messenger, Telegram, email, and website chat. LinkedIn integration is planned.'],
                ['How do reps get notified of hot leads?', 'Slack, Teams, email, or in-app push — configurable per rep and per lead score threshold.'],
            ],
        ],

        'support-teams' => [
            'role_label'  => 'Support Teams',
            'keyword'     => 'Unified Inbox for Support Teams',
            'headline'    => 'Every Customer Question in One Queue — With AI Handling 70% Automatically',
            'subhead'     => 'Support teams juggle Zendesk, WhatsApp, Instagram DMs, and email — hitting SLA targets on none. OT1-Pro unifies every support channel with AI auto-resolving routine questions so agents focus on complex cases.',
            'pain_intro'  => 'Support teams struggle with channel fragmentation:',
            'pain_points' => [
                'FAQs get answered 100 times a week by expensive human agents.',
                'Response SLAs vary by channel — WhatsApp expects instant, email tolerates hours, tickets get lost.',
                'Same customer asks the same question on 3 channels — nobody sees the duplication.',
                'CSAT drops because agents burn out on repetitive questions instead of solving hard ones.',
            ],
            'metrics' => [
                ['First-response time', '< 30s', 'across all channels'],
                ['AI resolution rate', '72%', 'FAQ, order status, sizing'],
                ['Agent utilization', '65%', 'healthy, not burnout'],
            ],
            'use_cases' => [
                ['🤖', 'AI FAQ Resolution', 'Product info, order status, return policy, shipping — AI answers instantly from your knowledge base.'],
                ['📦', 'Order Lookup', 'Direct integration with Shopify/WooCommerce — AI checks orders and gives accurate updates.'],
                ['💬', 'Sentiment-Based Escalation', 'Frustrated customers auto-escalate to a human within 30 seconds.'],
                ['🎫', 'Cross-Channel Ticketing', 'Same customer asking on WhatsApp + email? One ticket, one thread.'],
                ['📊', 'SLA Enforcement', 'Response-time targets per channel, auto-alerts when at risk.'],
                ['👥', 'Skill-Based Routing', 'Technical questions to tech support, billing to finance, complaints to seniors.'],
            ],
            'faq' => [
                ['Do I replace my helpdesk (Zendesk, Freshdesk) with this?', 'For most B2C and mid-market — yes. For enterprise with complex ticket routing, OT1-Pro complements the helpdesk on messaging channels.'],
                ['How does AI know how to answer support questions?', 'You upload your product catalog, FAQs, and policies. AI answers from that knowledge base — never invents.'],
                ['What happens when AI escalates?', 'Customer is told a human is joining. The agent sees full conversation context — no repeated questions.'],
                ['Can I set different SLAs per channel?', 'Yes — WhatsApp 2 min, Instagram 5 min, email 2 hours are configurable per channel + priority.'],
            ],
        ],

        'agencies' => [
            'role_label'  => 'Agencies',
            'keyword'     => 'Unified Inbox for Agencies',
            'headline'    => 'Manage Every Client\'s Customer Conversations From One Inbox',
            'subhead'     => 'Agencies running social media, customer support, or lead gen for multiple clients drown in switching between 5+ client accounts across 20+ channels. OT1-Pro lets one team member manage 10 clients from one screen.',
            'pain_intro'  => 'Agencies serving multi-client operations face compounding chaos:',
            'pain_points' => [
                'Each client has their own WhatsApp Business account, Instagram page, Facebook page — 15+ logins to juggle.',
                'Client dashboards live in Google Sheets because no tool shows performance across clients.',
                'Junior team members context-switch between brands, applying the wrong tone to the wrong client.',
                'White-label reporting takes days per month per client.',
            ],
            'metrics' => [
                ['Clients per team member', '4× more', 'was 2-3, now 8-12'],
                ['Client onboarding time', '2 hours', 'was 2 days'],
                ['Monthly reporting time', '10× faster', 'auto-generated dashboards'],
            ],
            'use_cases' => [
                ['🏢', 'Multi-Client Workspaces', 'Each client is a separate workspace with its own channels, AI voice, and reporting — one login.'],
                ['🎨', 'Per-Client Brand Voice', 'AI adapts tone per client — casual for the D2C brand, formal for the B2B firm.'],
                ['👥', 'Team Assignment by Client', 'Assign account managers to clients, junior reps to specific channels within them.'],
                ['📊', 'White-Label Client Reports', 'Auto-generated monthly reports branded as your agency — send to clients in one click.'],
                ['💼', 'Client-Facing Dashboards', 'Clients see their own metrics through a branded login — reduces "how are we doing?" pings.'],
                ['💰', 'Agency Pricing Model', 'Agency tier includes multi-workspace with predictable per-client pricing — no per-conversation surprises.'],
            ],
            'faq' => [
                ['Can I white-label OT1-Pro for my clients?', 'Yes — agency tier includes white-label options: your logo, your colors, client dashboards under your brand.'],
                ['How is pricing structured for agencies?', 'Per client workspace with unlimited channels within each, plus per-team-member seats. Predictable at scale.'],
                ['Can I onboard clients without them touching the platform?', 'Yes — you manage everything on their behalf. Clients only see the reporting dashboard if you enable it.'],
                ['Does it work with our existing agency stack (Notion, ClickUp, HubSpot)?', 'Yes — integrations connect to project management tools and CRMs so client work stays in your existing workflows.'],
            ],
        ],

        'customer-success-teams' => [
            'role_label'  => 'Customer Success Teams',
            'keyword'     => 'Unified Inbox for Customer Success Teams',
            'headline'    => 'Track Every Customer Signal Across Every Channel',
            'subhead'     => 'Customer success managers juggle Slack Connect channels, email, Zoom follow-ups, WhatsApp check-ins, and NPS surveys — losing early churn signals in the noise. OT1-Pro unifies every touchpoint with AI flagging at-risk accounts.',
            'pain_intro'  => 'Customer success teams face signal-loss across channels:',
            'pain_points' => [
                'Early churn signals hide in casual Slack Connect messages nobody catches.',
                'Renewal conversations happen in email while product feedback lives in Intercom — no single view.',
                'CSMs spend 6+ hours a week on manual status updates and account notes.',
                'QBRs prep takes days because customer history is scattered across 5 tools.',
            ],
            'metrics' => [
                ['Churn signals caught early', '+62%', 'AI flags at-risk accounts'],
                ['Accounts per CSM', '2× more', 'less admin, more strategy'],
                ['QBR prep time', '90% less', 'auto-generated summaries'],
            ],
            'use_cases' => [
                ['🚩', 'At-Risk Detection', 'AI reads customer messages for churn signals — usage decline, sentiment drop, competitor mentions.'],
                ['📅', 'Renewal Sequences', 'AI runs 60/30/15-day renewal cadences with personalized outreach.'],
                ['📈', 'Health Score Sync', 'Conversation sentiment feeds into account health scores in your CS platform (Gainsight, ChurnZero).'],
                ['💌', 'NPS + CSAT Automation', 'Post-interaction surveys auto-send; low scores trigger CSM outreach in 15 minutes.'],
                ['🎯', 'Expansion Trigger', 'Feature requests from strong accounts flag to sales as expansion opportunities.'],
                ['📝', 'Auto-Generated QBRs', 'Quarterly business review summaries drafted from actual customer interactions.'],
            ],
            'faq' => [
                ['Does this replace Gainsight or ChurnZero?', 'No — it augments them by feeding conversation data + sentiment into your existing CS platform via API.'],
                ['Can I connect Slack Connect channels?', 'Yes — Slack Connect messages flow into the unified inbox alongside email and messaging apps.'],
                ['How does AI detect churn risk?', 'Combination of message sentiment, engagement frequency change, keyword patterns ("cancel", "not working", competitor mentions), and configurable custom triggers.'],
                ['What integrations exist for CS tools?', 'Gainsight, ChurnZero, HubSpot, Salesforce, Hubspot, Intercom, plus webhook-based custom integrations.'],
            ],
        ],

        'devops-teams' => [
            'role_label'  => 'DevOps Teams',
            'keyword'     => 'Unified Inbox for DevOps Teams',
            'headline'    => 'Every Alert, Incident, and Escalation in One Priority Queue',
            'subhead'     => 'DevOps teams juggle PagerDuty, Slack alerts, GitHub notifications, customer bug reports, and stakeholder pings — leading to alert fatigue and missed incidents. OT1-Pro consolidates every signal with AI severity scoring.',
            'pain_intro'  => 'DevOps teams battle alert fatigue and signal loss:',
            'pain_points' => [
                'PagerDuty, Opsgenie, Sentry, Datadog, and CloudWatch each fire alerts independently — hundreds per day.',
                'True incidents get buried in noise from low-priority warnings.',
                'Customer bug reports via email/Slack take hours to reach the on-call engineer.',
                'Post-incident retros lack conversation history across channels.',
            ],
            'metrics' => [
                ['Alert noise reduction', '−78%', 'AI dedupes + severity-scores'],
                ['Time to acknowledge (TTA)', '< 60s', 'from 8 min average'],
                ['Escalations missed', '0', 'AI ensures every P1 sees a human'],
            ],
            'use_cases' => [
                ['🚨', 'Alert Deduplication', 'AI groups related alerts across tools — one incident, one thread, not 15.'],
                ['⚡', 'Severity Scoring', 'AI classifies P0 vs P1 vs P2 based on content — routes P0 immediately, batches P2.'],
                ['📞', 'On-Call Rotation Sync', 'Integrates with PagerDuty/Opsgenie schedules — right person, right time.'],
                ['📝', 'Incident Timeline', 'Every message during an incident logged with timestamps for post-mortem.'],
                ['🔗', 'Cross-Tool Context', 'Sentry error + Datadog metric + customer bug report all linked to one incident thread.'],
                ['💬', 'Stakeholder Updates', 'AI drafts status updates for exec Slack channels during incidents — no manual work.'],
            ],
            'faq' => [
                ['Does this replace PagerDuty?', 'No — it sits above PagerDuty and other alerting tools, consolidating their outputs with AI triage.'],
                ['Which observability tools integrate?', 'Datadog, Sentry, New Relic, CloudWatch, Grafana, Prometheus alerts via webhook. PagerDuty and Opsgenie for on-call routing.'],
                ['Can I use it for internal team communication too?', 'Yes — Slack integration means engineer-to-engineer chat stays where it is; external signals flow into the unified inbox.'],
                ['How does AI avoid false-negative severity scoring?', 'Configurable rules override AI classification. Every P0 also triggers a fallback human review within 60 seconds.'],
            ],
        ],

        'hr-teams' => [
            'role_label'  => 'HR Teams',
            'keyword'     => 'Unified Inbox for HR Teams',
            'headline'    => 'Every Employee and Candidate Conversation in One Inbox',
            'subhead'     => 'HR teams juggle candidate emails, employee questions, Slack DMs, WhatsApp check-ins, and Workday notifications — leading to missed candidates and slow employee support. OT1-Pro unifies every HR touchpoint with AI triage.',
            'pain_intro'  => 'HR teams face fragmented conversations at every stage:',
            'pain_points' => [
                'Candidates message across email, LinkedIn, WhatsApp — top talent goes cold from slow responses.',
                'Employee questions ("what\'s my PTO balance?") flood HR with the same 20 questions weekly.',
                'Onboarding new hires requires 15+ messages across tools — always something falls through.',
                'Sensitive conversations (grievances, personal issues) need discretion the wrong tool doesn\'t provide.',
            ],
            'metrics' => [
                ['Candidate response time', '< 2 hours', 'from 2 days average'],
                ['Employee FAQ auto-resolve', '68%', 'PTO, benefits, policies'],
                ['Onboarding completion rate', '+45%', 'nothing forgotten'],
            ],
            'use_cases' => [
                ['👋', 'Candidate Communication', 'AI acknowledges applications instantly, schedules screens, follows up on offers.'],
                ['📚', 'Employee FAQ Bot', 'Policies, benefits, PTO, expense process — AI answers instantly from HR handbook.'],
                ['🎓', 'Onboarding Sequences', 'Day 1, Week 1, Month 1 check-ins automated across email + Slack + calendar.'],
                ['🔒', 'Confidential Conversations', 'Encrypted 1:1 channels for sensitive HR discussions — full audit trail.'],
                ['📊', 'Engagement Surveys', 'Pulse surveys via Slack DM or email, results auto-aggregated for HRBP review.'],
                ['🎯', 'Manager Enablement', 'Managers get AI-drafted responses to team questions, keeping HR out of routine queries.'],
            ],
            'faq' => [
                ['Does this replace Workday, BambooHR, or our HRIS?', 'No — it complements them. HRIS holds system-of-record data; OT1-Pro handles the conversations around that data.'],
                ['How does AI handle sensitive employee conversations?', 'AI never handles sensitive topics (grievances, performance issues) — those always route to a human immediately.'],
                ['Can we track EEOC/compliance requirements?', 'Yes — every candidate conversation logged with timestamps and content for compliance reviews.'],
                ['Does it integrate with our ATS (Greenhouse, Lever, Workable)?', 'Yes — candidate conversation history syncs bidirectionally so recruiters see everything in one place.'],
            ],
        ],

        'marketing-teams' => [
            'role_label'  => 'Marketing Teams',
            'keyword'     => 'Unified Inbox for Marketing Teams',
            'headline'    => 'Every Campaign Reply, Comment, and DM in One Inbox',
            'subhead'     => 'Marketing teams launch campaigns across Instagram, Facebook, WhatsApp, email, and paid ads — then get buried in replies split across 8 different consoles. OT1-Pro unifies every campaign response with AI qualifying leads on the spot.',
            'pain_intro'  => 'Marketing teams lose campaign ROI to reply chaos:',
            'pain_points' => [
                'Campaign replies flood in across every channel — team can\'t keep up, leads go cold.',
                'Click-to-Message ads generate 100+ DMs; only 30% get replies within an hour.',
                'Instagram comments never convert because manually replying to 500 comments/post is impossible.',
                'No unified view of which campaign generated which conversations = attribution guesswork.',
            ],
            'metrics' => [
                ['Campaign reply-to-close rate', '+52%', 'AI qualifies instantly'],
                ['Cost per qualified lead', '−41%', 'from ad spend efficiency'],
                ['Instagram comment → DM', 'Automated', 'zero manual triage'],
            ],
            'use_cases' => [
                ['📢', 'Campaign Reply Handling', 'Every reply to every ad, post, or email flows into one inbox with campaign attribution.'],
                ['💬', 'Comment-to-DM Automation', 'Instagram/Facebook post comments trigger DM sequences based on keywords.'],
                ['🎯', 'Real-Time Lead Qualification', 'AI qualifies campaign leads with 2-3 questions before routing to sales.'],
                ['📊', 'Attribution Reporting', 'See which campaign/creative/keyword drove which closed deal — end-to-end.'],
                ['🔥', 'Retargeting Signals', 'Conversation intent feeds custom audiences for lookalike targeting.'],
                ['📝', 'Content Repurposing', 'Common customer questions surface as content ideas for blog, social, or FAQ.'],
            ],
            'faq' => [
                ['Does this replace HubSpot Marketing or Marketo?', 'No — it complements them. Marketing automation platforms send campaigns; OT1-Pro handles the two-way conversations campaigns generate.'],
                ['Can I track ROI per campaign?', 'Yes — every conversation tagged with source campaign, UTM, and channel. Full funnel attribution to closed deal.'],
                ['How does the Instagram comment-to-DM automation work?', 'Set trigger keywords per post — when someone comments the keyword, they automatically get a DM with the catalog, offer, or qualification questions.'],
                ['What about paid ad campaigns (Meta, Google, TikTok)?', 'Click-to-Message ads on Meta/TikTok flow directly into the inbox. Google Lead Form Extensions integrate via webhook.'],
            ],
        ],
    ];

    public function show(string $role): Response
    {
        $config = self::ROLES[$role] ?? throw new NotFoundHttpException();

        // Cache the rendered view per role — content is static, no need to re-render.
        $html = Cache::remember(
            "vertical-landing:{$role}",
            now()->addHours(6),
            fn () => view('pages.vertical-landing', [
                'role'   => $role,
                'config' => $config,
            ])->render(),
        );

        return response($html, 200, ['Content-Type' => 'text/html; charset=UTF-8']);
    }

    /**
     * List of all supported role slugs. Used by the sitemap generator and
     * the route constraint. Keep in sync with ::ROLES keys.
     */
    public static function roleSlugs(): array
    {
        return array_keys(self::ROLES);
    }
}
