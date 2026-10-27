<?php

declare(strict_types=1);

use App\Services\Platforms\FacebookPlatform;

uses(Tests\TestCase::class);

/*
 * Scope regression pins. Context:
 *   • 2026-10-25 (commit 7137fbf) swapped pages_manage_engagement → pages_manage_posts
 *     because of a believed OAuth-validator bundling bug ("Invalid Scopes:
 *     pages_read_user_content" when requesting pages_manage_engagement).
 *   • 2026-10-27 Dev Console audit (tasks/meta-app-permissions-snapshot.md)
 *     confirmed no such bundling exists today — pages_manage_engagement is a
 *     clean row in the Pages API use case, Standard Access, no deprecated
 *     dependency. The 10-25 swap caused Brandk's "Public reply failed (#200)
 *     The permission(s) pages_read_user_content are not available" bug because
 *     pages_manage_posts is for the Page's own feed publishing, not for
 *     replying to users' comments (POST /{comment-id}/comments).
 *   • Both scopes are now requested together. Keep BOTH pinned so a future
 *     "simplification" PR can't silently drop pages_manage_engagement again
 *     (that regression took ~2 days to notice in prod).
 */

it('requests BOTH pages_manage_posts AND pages_manage_engagement in the FB-only OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getConnectUrl();
    expect($url)->toContain('pages_manage_posts');
    expect($url)->toContain('pages_manage_engagement');
    // Regression safety: existing required scopes must still be present.
    expect($url)->toContain('pages_messaging');
    expect($url)->toContain('pages_read_engagement');
    expect($url)->toContain('pages_show_list');
    expect($url)->toContain('pages_manage_metadata');
    expect($url)->toContain('business_management');
});

it('requests BOTH pages_manage_posts AND pages_manage_engagement in the FB+IG combined OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getInstagramViaFacebookConnectUrl();
    expect($url)->toContain('pages_manage_posts');
    expect($url)->toContain('pages_manage_engagement');
    expect($url)->toContain('instagram_manage_comments');
    expect($url)->toContain('pages_messaging');
    expect($url)->toContain('instagram_basic');
    expect($url)->toContain('instagram_manage_messages');
    expect($url)->toContain('business_management');
});
