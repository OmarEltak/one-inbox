<?php

declare(strict_types=1);

use App\Services\Platforms\FacebookPlatform;

uses(Tests\TestCase::class);

/*
 * Scope regression pin — both pages_manage_posts AND pages_manage_engagement
 * must be present in both FB OAuth URLs.
 *
 * History (the previous author of this pin flipped it multiple times — the
 * resolution is in the comment on FacebookPlatform::getConnectUrl, don't
 * repeat the swap debate):
 *   • ea12a17 — pages_manage_engagement originally removed
 *   • cc3cfc0 → f692bda — re-added, OAuth broke, reverted
 *   • 7137fbf — swapped to pages_manage_posts (pin FORBID pages_manage_engagement)
 *   • 49b8815 → 6b75c62 — re-added after Dev Console audit; OAuth broke again
 *     because the Dev Console UI does not reveal OAuth-validator dependencies;
 *     reverted
 *   • Meta support ticket 2162436254672944 (opened 2026-10-27) explained the
 *     root cause: pages_manage_engagement has an internal dependency on
 *     pages_read_user_content; the validator rejects OAuth when any requested
 *     scope's dependency is not granted to the app
 *   • Fix (2026-10-28): added pages_read_user_content to the Pages API use
 *     case on developers.facebook.com → Meta auto-granted Standard Access →
 *     live OAuth now loads the consent screen cleanly
 *   • THIS pin — both scopes REQUIRED
 *
 * Pre-condition for this pin to stay green on prod: pages_read_user_content
 * MUST remain in the Pages API use case at Standard Access or higher. If it
 * is ever removed from the use case, OAuth will break again with the same
 * "Invalid Scopes: pages_read_user_content" error.
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
