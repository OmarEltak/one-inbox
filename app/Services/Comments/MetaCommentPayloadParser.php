<?php

declare(strict_types=1);

namespace App\Services\Comments;

class MetaCommentPayloadParser
{
    /**
     * @param  array<string, mixed>  $change  One item from entry.changes[]
     * @return array{
     *     platform: 'facebook'|'instagram',
     *     platform_comment_id: string,
     *     platform_post_id: string,
     *     parent_comment_id: string|null,
     *     commenter_platform_id: string,
     *     commenter_name: string,
     *     text: string,
     *     received_at: \Carbon\Carbon,
     * }|null
     */
    public function parse(array $change): ?array
    {
        $field = $change['field'] ?? null;
        $value = $change['value'] ?? [];

        return match ($field) {
            'feed'     => $this->parseFacebookFeed($value),
            'comments' => $this->parseInstagramComments($value),
            default    => null,
        };
    }

    /** @param array<string, mixed> $value */
    protected function parseFacebookFeed(array $value): ?array
    {
        if (($value['item'] ?? null) !== 'comment') {
            return null;
        }
        if (($value['verb'] ?? 'add') !== 'add') {
            return null;
        }
        $commentId = $value['comment_id'] ?? null;
        $postId    = $value['post_id'] ?? null;
        $from      = $value['from'] ?? [];
        $message   = $value['message'] ?? '';

        if (! $commentId || ! $postId || empty($from['id'])) {
            return null;
        }

        return [
            'platform'              => 'facebook',
            'platform_comment_id'   => (string) $commentId,
            'platform_post_id'      => (string) $postId,
            'parent_comment_id'     => isset($value['parent_id']) ? (string) $value['parent_id'] : null,
            'commenter_platform_id' => (string) $from['id'],
            'commenter_name'        => (string) ($from['name'] ?? 'Unknown'),
            'text'                  => (string) $message,
            'received_at'           => isset($value['created_time'])
                ? \Carbon\Carbon::createFromTimestamp((int) $value['created_time'])
                : now(),
        ];
    }

    /** @param array<string, mixed> $value */
    protected function parseInstagramComments(array $value): ?array
    {
        $commentId = $value['id'] ?? null;
        $mediaId   = $value['media']['id'] ?? null;
        $from      = $value['from'] ?? [];
        $text      = $value['text'] ?? '';

        if (! $commentId || ! $mediaId || empty($from['id'])) {
            return null;
        }

        return [
            'platform'              => 'instagram',
            'platform_comment_id'   => (string) $commentId,
            'platform_post_id'      => (string) $mediaId,
            'parent_comment_id'     => isset($value['parent_id']) ? (string) $value['parent_id'] : null,
            'commenter_platform_id' => (string) $from['id'],
            'commenter_name'        => (string) ($from['username'] ?? 'Unknown'),
            'text'                  => (string) $text,
            'received_at'           => now(),
        ];
    }
}
