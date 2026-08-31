<?php

declare(strict_types=1);

use App\Models\AiConfig;

it('exposes safe default comment settings', function () {
    $defaults = AiConfig::defaultCommentSettings();

    expect($defaults)->toBe([
        'enabled'                          => false,
        'enabled_at'                       => null,
        'reply_mode'                       => AiConfig::COMMENT_REPLY_OFF,
        'reply_keywords'                   => [],
        'dm_mode'                          => AiConfig::COMMENT_DM_OFF,
        'dm_keywords'                      => [],
        'reply_instructions'               => '',
        'scope'                            => AiConfig::COMMENT_SCOPE_FUTURE_ONLY,
        'max_ai_replies_per_post_per_day'  => 20,
    ]);
});

it('casts comment_settings as array on round-trip', function () {
    $config = new AiConfig();
    $config->comment_settings = ['enabled' => true, 'reply_mode' => 'all'];

    $encoded = $config->getAttributes()['comment_settings'];
    expect($encoded)->toBeString();

    $decoded = json_decode($encoded, true);
    expect($decoded)->toBe(['enabled' => true, 'reply_mode' => 'all']);
});

it('exposes the expected constants', function () {
    expect(AiConfig::COMMENT_REPLY_OFF)->toBe('off');
    expect(AiConfig::COMMENT_REPLY_ALL)->toBe('all');
    expect(AiConfig::COMMENT_REPLY_QUESTIONS_AND_COMPLAINTS)->toBe('questions_and_complaints');
    expect(AiConfig::COMMENT_REPLY_CUSTOM_KEYWORDS)->toBe('custom_keywords');

    expect(AiConfig::COMMENT_DM_OFF)->toBe('off');
    expect(AiConfig::COMMENT_DM_ALWAYS)->toBe('always');
    expect(AiConfig::COMMENT_DM_ON_PURCHASE_INTENT)->toBe('on_purchase_intent');

    expect(AiConfig::COMMENT_SCOPE_FUTURE_ONLY)->toBe('future_posts_only');
    expect(AiConfig::COMMENT_SCOPE_ALL_POSTS)->toBe('all_posts');

    expect(AiConfig::COMMENT_MAX_REPLIES_PER_POST_MIN)->toBe(1);
    expect(AiConfig::COMMENT_MAX_REPLIES_PER_POST_MAX)->toBe(100);
});
