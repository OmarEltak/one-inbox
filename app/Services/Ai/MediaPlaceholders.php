<?php

namespace App\Services\Ai;

/**
 * Attachments are stored with placeholder text ("[image]", "[voice note]")
 * when there is no caption, transcript or vision description. Fed to the
 * model verbatim, weak models copy them back as the whole reply — a customer
 * received an AI bubble reading "[image]\n\n[voice note]" (two outbound media
 * turns merged by coalesceRoles, then mimicked).
 *
 * So the history narrates media in words ("[media: you sent the customer a
 * photo]"), and every reply is passed through strip() before it is sent.
 */
final class MediaPlaceholders
{
    /** Bracket tokens that stand in for an attachment, plus our own narration tag. */
    private const TOKEN = '/\[(?:media:[^\]]*|image|photo|voice note|audio|audio\/voice message|video|document|document\/file|file|media|sticker|reaction|shared a file)\]/iu';

    private const KIND_BY_PLACEHOLDER = [
        'image' => 'image', 'photo' => 'image', 'voice note' => 'audio', 'audio' => 'audio',
        'audio/voice message' => 'audio', 'video' => 'video', 'document' => 'file',
        'document/file' => 'file', 'file' => 'file', 'sticker' => 'sticker', 'reaction' => 'reaction',
    ];

    /** True when the whole message body is a placeholder (no real text). */
    public static function isPlaceholder(?string $content): bool
    {
        return $content === null || trim($content) === '' || self::strip($content) === '';
    }

    /** Words the model can reason about but has no reason to repeat. */
    public static function narrate(?string $contentType, ?string $content, bool $inbound): string
    {
        $key = strtolower(trim((string) $content, " []\t\n"));
        $kind = self::KIND_BY_PLACEHOLDER[$key] ?? $contentType;

        $what = match ($kind) {
            'image'    => 'a photo',
            'audio'    => 'a voice note',
            'video'    => 'a video',
            'file', 'document' => 'a file',
            'sticker'  => 'a sticker',
            'reaction' => 'a reaction',
            default    => 'an attachment',
        };

        return $inbound
            ? "[media: the customer sent {$what} you cannot see or hear — never repeat this tag; if it matters, ask briefly what it was]"
            : "[media: you sent the customer {$what}]";
    }

    /** Remove placeholder tokens from a reply; '' when nothing real is left. */
    public static function strip(string $reply): string
    {
        $clean = preg_replace(self::TOKEN, '', $reply) ?? $reply;
        $clean = preg_replace("/\n{3,}/", "\n\n", $clean) ?? $clean;

        return trim($clean);
    }
}
