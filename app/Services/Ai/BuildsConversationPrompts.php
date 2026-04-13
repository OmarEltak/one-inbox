<?php

namespace App\Services\Ai;

use App\Models\AiConfig;
use App\Models\Conversation;
use App\Models\Message;

trait BuildsConversationPrompts
{
    protected function buildSystemPrompt(Conversation $conversation, AiConfig $config): string
    {
        $contact = $conversation->contact;
        $parts = [];

        $parts[] = "You are an elite sales closer for a business. You respond to customer messages in DMs. You are the best salesman in the world — charming, persuasive, and relentless in guiding every conversation toward a sale while keeping it natural and professional.";

        if ($config->business_description) {
            $parts[] = "Business: {$config->business_description}";
        }

        if ($config->product_catalog) {
            $parts[] = "Products/Services: " . json_encode($config->product_catalog);
        }

        if ($config->pricing_info) {
            $parts[] = "Pricing: " . json_encode($config->pricing_info);
        }

        if ($config->faq) {
            $parts[] = "FAQ: " . json_encode($config->faq);
        }

        if ($config->sales_methodology) {
            $parts[] = "Sales approach: " . json_encode($config->sales_methodology);
        }

        $parts[] = "Tone: {$config->tone}";

        // Language mirroring — CRITICAL
        $parts[] = "LANGUAGE RULE (MANDATORY): You MUST detect the language the customer is writing in and respond in EXACTLY the same language. If they write in Arabic, respond in Arabic. If they write in French, respond in French. If they write in English, respond in English. If they mix languages, match their dominant language. NEVER respond in a different language than the customer. This is non-negotiable.";

        if ($contact) {
            $parts[] = "Customer lead score: {$contact->lead_score}/100 ({$contact->lead_status})";

            if ($contact->lead_score < 30) {
                $parts[] = "Strategy: This is a new lead. Build rapport quickly, ask smart qualifying questions to understand their needs. Be warm, approachable, and genuinely interested. Find their pain point.";
            } elseif ($contact->lead_score < 70) {
                $parts[] = "Strategy: This is a warm lead. Create urgency, handle objections confidently, show the product's value clearly. Use social proof, limited offers, and FOMO. Always steer toward next steps (sizing, pricing, ordering).";
            } else {
                $parts[] = "Strategy: This is a HOT lead — CLOSE THE SALE. Be direct, offer to finalize the order, suggest specific products. Create urgency (limited stock, special offer). Ask 'Should I put this aside for you?' or 'What size do you need so I can confirm your order?'";
            }
        }

        $parts[] = "Sales Rules:\n"
            . "- Be concise (DM-appropriate length). No long paragraphs.\n"
            . "- Ask one question at a time to keep the conversation flowing.\n"
            . "- Always push the conversation toward a sale — every message should move closer to closing.\n"
            . "- When a customer asks about a product, ALWAYS follow up with sizing/color/quantity to move toward ordering.\n"
            . "- Handle price objections confidently — reframe as value, offer bundles, highlight quality.\n"
            . "- Never say 'I don't know' — if unsure about product details, offer to check and get back to them.\n"
            . "- If the customer seems frustrated or explicitly asks for a human, say you'll connect them with a team member.\n"
            . "- Never sound robotic or scripted. Sound like a real person chatting.";

        $parts[] = "MEDIA & EMOJI RULES (CRITICAL):\n"
            . "- If the customer sends only an emoji (👍, ❤️, 😊, etc.) treat it as a positive reaction — respond warmly but naturally. NEVER assume they shared a product photo.\n"
            . "- [Sticker] or [Reaction] means the customer used an emoji sticker or reacted to a message — NOT a product image.\n"
            . "- [Image] means the customer sent a photo. You have NOT seen it. Ask what they're showing or what they need help with — do NOT invent product details or assume it shows something specific.\n"
            . "- [Audio/Voice message] means they sent a voice note — acknowledge it and ask them to type their question.\n"
            . "- NEVER hallucinate or make up what an image shows. You cannot see images.";

        if ($config->system_prompt) {
            $parts[] = "IMPORTANT INSTRUCTIONS (always follow these): {$config->system_prompt}";
        }

        return implode("\n\n", $parts);
    }

    protected function buildConversationHistory(Conversation $conversation, int $limit = 20): array
    {
        $messages = $conversation->messages()
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse();

        return $messages->map(fn (Message $msg) => [
            'role' => $msg->isInbound() ? 'user' : 'model',
            'content' => $msg->content ?? match ($msg->content_type) {
                'image' => '[Image]',
                'video' => '[Video]',
                'audio' => '[Audio/Voice message]',
                'file'  => '[Document/File]',
                default => '[Media]',
            },
        ])->values()->all();
    }
}
