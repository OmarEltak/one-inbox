<?php

declare(strict_types=1);

use App\Services\Comments\MetaCommentPayloadParser;

it('parses a Facebook feed comment change', function () {
    $change = [
        'field' => 'feed',
        'value' => [
            'item'         => 'comment',
            'comment_id'   => '123_456',
            'post_id'      => '123',
            'from'         => ['id' => 'user_1', 'name' => 'Ada Lovelace'],
            'message'      => 'How much does this cost?',
            'created_time' => 1756636800,
            'verb'         => 'add',
        ],
    ];

    $parsed = (new MetaCommentPayloadParser())->parse($change);

    expect($parsed)->not->toBeNull();
    expect($parsed['platform_comment_id'])->toBe('123_456');
    expect($parsed['platform_post_id'])->toBe('123');
    expect($parsed['commenter_platform_id'])->toBe('user_1');
    expect($parsed['commenter_name'])->toBe('Ada Lovelace');
    expect($parsed['text'])->toBe('How much does this cost?');
    expect($parsed['parent_comment_id'])->toBeNull();
});

it('parses an Instagram comment change', function () {
    $change = [
        'field' => 'comments',
        'value' => [
            'id'    => 'ig_comment_1',
            'text'  => 'Love this!',
            'from'  => ['id' => 'ig_user_1', 'username' => 'ada'],
            'media' => ['id' => 'ig_media_1'],
        ],
    ];

    $parsed = (new MetaCommentPayloadParser())->parse($change);

    expect($parsed)->not->toBeNull();
    expect($parsed['platform_comment_id'])->toBe('ig_comment_1');
    expect($parsed['platform_post_id'])->toBe('ig_media_1');
    expect($parsed['commenter_platform_id'])->toBe('ig_user_1');
    expect($parsed['commenter_name'])->toBe('ada');
    expect($parsed['text'])->toBe('Love this!');
});

it('returns null when field is not a comment field', function () {
    $parsed = (new MetaCommentPayloadParser())->parse(['field' => 'messages', 'value' => []]);
    expect($parsed)->toBeNull();
});

it('returns null when FB feed change is not a comment item', function () {
    $parsed = (new MetaCommentPayloadParser())->parse([
        'field' => 'feed',
        'value' => ['item' => 'like', 'post_id' => '123'],
    ]);
    expect($parsed)->toBeNull();
});

it('returns null when FB verb is not add', function () {
    $parsed = (new MetaCommentPayloadParser())->parse([
        'field' => 'feed',
        'value' => [
            'item' => 'comment', 'verb' => 'remove',
            'comment_id' => 'c1', 'post_id' => 'p1',
            'from' => ['id' => 'u1', 'name' => 'x'], 'message' => 'x',
        ],
    ]);
    expect($parsed)->toBeNull();
});

it('extracts parent_id when FB comment is a reply', function () {
    $parsed = (new MetaCommentPayloadParser())->parse([
        'field' => 'feed',
        'value' => [
            'item' => 'comment', 'verb' => 'add',
            'comment_id' => 'c2', 'post_id' => 'p1',
            'parent_id' => 'c1',
            'from' => ['id' => 'u1', 'name' => 'x'], 'message' => 'reply',
        ],
    ]);
    expect($parsed['parent_comment_id'])->toBe('c1');
});
