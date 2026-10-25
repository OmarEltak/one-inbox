<?php

declare(strict_types=1);

use App\Services\Platforms\FacebookPlatform;

uses(Tests\TestCase::class);

/*
 * pages_manage_posts MUST be present in both FB OAuth URLs (NOT
 * pages_manage_engagement). Meta's OAuth validator internally bundles
 * pages_manage_engagement with the deprecated pages_read_user_content and
 * rejects the whole OAuth with "Invalid Scopes: pages_read_user_content"
 * even though we never request the deprecated scope. pages_manage_posts
 * covers the same POST /{comment-id}/comments endpoint without the bundle.
 * Confirmed 2026-10-25 via browser OAuth test.
 */

it('requests pages_manage_posts (NOT pages_manage_engagement) in the FB-only OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getConnectUrl();
    expect($url)->toContain('pages_manage_posts');
    expect($url)->not->toContain('pages_manage_engagement');
    // Regression safety: existing required scopes must still be present.
    expect($url)->toContain('pages_messaging');
    expect($url)->toContain('pages_read_engagement');
    expect($url)->toContain('pages_show_list');
    expect($url)->toContain('pages_manage_metadata');
    expect($url)->toContain('business_management');
});

it('requests pages_manage_posts (NOT pages_manage_engagement) in the FB+IG combined OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getInstagramViaFacebookConnectUrl();
    expect($url)->toContain('pages_manage_posts');
    expect($url)->not->toContain('pages_manage_engagement');
    expect($url)->toContain('instagram_manage_comments');
    expect($url)->toContain('pages_messaging');
    expect($url)->toContain('instagram_basic');
    expect($url)->toContain('instagram_manage_messages');
    expect($url)->toContain('business_management');
});
