<?php

declare(strict_types=1);

namespace App\Services\Skills;

use App\Contracts\AiProviderInterface;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Team;
use Illuminate\Support\Collection;

/**
 * SKILL 1 — Conversation Auditor
 *
 * Scans recent AI replies for a team and flags rule violations. Each rule is
 * a cheap deterministic check (no LLM critic needed for the common cases).
 * Can be run ad-hoc (`php artisan tinker --execute="..."`) or on a schedule
 * (cron hourly for every team with ai_config active).
 *
 * Current ruleset (extend by adding to the $rules array):
 *   - R1: AI must not address the customer by first name (always use فندم)
 *   - R2: AI must not comment on the person wearing an item ("عليكِ", "شكلك")
 *   - R3: AI must not emit "سجلت الأوردر" without ALL of:
 *            name_line + phone_line + address_line + at least one شال line
 *   - R4: AI must not say "I can't see the image" if vision is enabled
 *         (symptom = DescribeImage race or failure)
 *   - R5: AI replies that are empty / only whitespace
 *
 * Returns a Collection<array>:
 *   [
 *     'conv_id' => int,
 *     'msg_id'  => int,
 *     'rule'    => string,
 *     'snippet' => string (first 140 chars of the offending message),
 *     'severity'=> 'high'|'medium'|'low',
 *   ]
 */
class ConversationAuditor
{
    /** Known Arabic first-name words to check for in "يا <name>" addressing. */
    private const COMMON_FIRST_NAMES = [
        'محمد', 'احمد', 'أحمد', 'هبه', 'سارة', 'ساره', 'نور', 'مريم',
        'ياسمين', 'رنا', 'منى', 'سلمى', 'آية', 'آيه', 'ايه', 'فاطمة',
        'فاطمه', 'إيمان', 'ايمان', 'ملك', 'لمى', 'لما', 'رهف', 'شريف',
    ];

    private const BODY_FLATTERY = [
        'عليكِ', 'عليكي', 'شكلك', 'بتلبسيه', 'لابساه', 'لابساها',
        'لابستيه', 'طالع عليكِ', 'طالع عليكي', 'بتلبسي',
    ];

    public function audit(int $teamId, int $hours = 24): Collection
    {
        $since = now()->subHours($hours);

        $aiMsgs = Message::whereHas('conversation.page', fn ($q) => $q->where('team_id', $teamId))
            ->where('sender_type', 'ai')
            ->where('direction', 'outbound')
            ->where('created_at', '>=', $since)
            ->whereNotNull('content')
            ->orderBy('id')
            ->get();

        $violations = collect();

        foreach ($aiMsgs as $m) {
            $content = (string) $m->content;
            if (trim($content) === '' && empty($m->media_url)) {
                $violations->push($this->mk($m, 'R5_empty_reply', $content, 'low'));
                continue;
            }

            foreach (self::COMMON_FIRST_NAMES as $name) {
                if (preg_match('/يا\s+' . preg_quote($name, '/') . '(?![\p{L}])/u', $content)) {
                    $violations->push($this->mk($m, 'R1_first_name_address', $content, 'medium'));
                    break;
                }
            }

            foreach (self::BODY_FLATTERY as $phrase) {
                if (str_contains($content, $phrase)) {
                    $prevImg = Message::where('conversation_id', $m->conversation_id)
                        ->where('id', '<', $m->id)
                        ->where('content_type', 'image')
                        ->where('direction', 'inbound')
                        ->exists();
                    if ($prevImg) {
                        $violations->push($this->mk($m, 'R2_body_flattery_after_customer_image', $content, 'high'));
                        break;
                    }
                }
            }

            if (str_contains($content, 'سجلت الأوردر')) {
                $missing = [];
                if (! preg_match('/[-•]?\s*الاسم\s*[:：]/u', $content))        $missing[] = 'name_line';
                if (! preg_match('/[-•]?\s*رقم\s+التواصل\s*[:：]/u', $content)) $missing[] = 'phone_line';
                if (! preg_match('/[-•]?\s*العنوان\s*[:：]/u', $content))     $missing[] = 'address_line';
                if (! preg_match('/[-•]\s*[^\n]*شال[^\n]*/u', $content))      $missing[] = 'order_line';
                if (! empty($missing)) {
                    $violations->push($this->mk($m, 'R3_incomplete_confirmation:' . implode(',', $missing), $content, 'high'));
                }
            }

            $visionFailPhrases = ['مش ظاهرة', 'مش واضح', 'ما قدرت أشوف', 'ما وصلني'];
            foreach ($visionFailPhrases as $p) {
                if (str_contains($content, $p)) {
                    $hadImg = Message::where('conversation_id', $m->conversation_id)
                        ->where('id', '<=', $m->id)
                        ->where('direction', 'inbound')
                        ->where('content_type', 'image')
                        ->where('created_at', '>=', $m->created_at->copy()->subMinutes(10))
                        ->exists();
                    if ($hadImg) {
                        $violations->push($this->mk($m, 'R4_vision_fail_phrase', $content, 'medium'));
                    }
                    break;
                }
            }
        }

        return $violations;
    }

    private function mk(Message $m, string $rule, string $content, string $severity): array
    {
        return [
            'conv_id'  => $m->conversation_id,
            'msg_id'   => $m->id,
            'rule'     => $rule,
            'snippet'  => mb_substr(preg_replace('/\s+/u', ' ', $content), 0, 140),
            'severity' => $severity,
            'at'       => $m->created_at->format('Y-m-d H:i'),
        ];
    }
}
