<?php

namespace App\Contracts;

use App\Models\AiConfig;
use App\Models\Contact;
use App\Models\Conversation;
use App\Models\Message;

interface AiProviderInterface
{
    /**
     * Generate a sales response for an incoming message.
     */
    public function generateResponse(Conversation $conversation, Message $incomingMessage, AiConfig $config): string;

    /**
     * Analyze a message and return lead scoring signals.
     * Returns array of ['event_type' => string, 'score_change' => int, 'reason' => string]
     */
    public function scoreMessage(Message $message, Contact $contact): array;

    /**
     * Analyze a full conversation and return insights.
     */
    public function analyzeConversation(Conversation $conversation): array;

    /**
     * Process a natural language command from the admin command center.
     */
    public function processCommand(string $command, int $teamId): array;

    /**
     * Generate a text response given a system prompt and a single user message.
     * Used for one-off AI calls (e.g. personalized follow-up generation).
     */
    public function generateText(string $systemPrompt, string $userMessage): string;
}
