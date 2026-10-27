<?php

declare(strict_types=1);

use App\Services\Platforms\FacebookPlatform;

uses(Tests\TestCase::class);

/*
 * Scope regression pin — pages_manage_engagement MUST NOT be in either FB
 * OAuth URL. CONFIRMED 2026-10-27 via live OAuth test: Meta's OAuth
 * validator still internally bundles pages_manage_engagement with the
 * deprecated pages_read_user_content and rejects the whole OAuth with
 * "Invalid Scopes: pages_read_user_content" — the user-facing error page
 * is Arabic "هذا المحتوى غير متوفر الآن" and the whole connect flow is
 * broken. This has been attempted and reverted five times:
 *   ea12a17 — originally removed
 *   cc3cfc0 — re-added
 *   f692bda — reverted re-add
 *   7137fbf — swapped to pages_manage_posts
 *   49b8815 — re-added again (regression) — reverted immediately on prod feedback
 * The current state (pages_manage_posts only) means FB AI comment replies
 * via POST /{comment-id}/comments return "pages_read_user_content not
 * available" at send-time (Brandk's visible bug at /comments). That is a
 * SEPARATE problem from the OAuth-side issue this test pins against — do
 * NOT try to fix it by re-adding pages_manage_engagement. See
 * tasks/meta-pages-manage-engagement-blocker.md for alternative paths.
 */

it('does NOT request pages_manage_engagement in the FB-only OAuth URL (OAuth validator bundling bug)', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getConnectUrl();
    expect($url)->not->toContain('pages_manage_engagement');
    expect($url)->toContain('pages_manage_posts');
    // Regression safety: existing required scopes must still be present.
    expect($url)->toContain('pages_messaging');
    expect($url)->toContain('pages_read_engagement');
    expect($url)->toContain('pages_show_list');
    expect($url)->toContain('pages_manage_metadata');
    expect($url)->toContain('business_management');
});

it('does NOT request pages_manage_engagement in the FB+IG combined OAuth URL (OAuth validator bundling bug)', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getInstagramViaFacebookConnectUrl();
    expect($url)->not->toContain('pages_manage_engagement');
    expect($url)->toContain('pages_manage_posts');
    expect($url)->toContain('instagram_manage_comments');
    expect($url)->toContain('pages_messaging');
    expect($url)->toContain('instagram_basic');
    expect($url)->toContain('instagram_manage_messages');
    expect($url)->toContain('business_management');
});
