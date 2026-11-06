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

/*
 * auth_type=rerequest pin — both FB OAuth URLs must include this parameter.
 *
 * Reason: Meta's Pages picker is sticky across OAuth runs. Without rerequest,
 * users who previously unchecked a Page in the picker CANNOT re-tick it by
 * re-running "Reconnect" — Meta reuses the old selection silently, no picker
 * is shown, and the new Page's token lacks all pages_* granular scopes. The
 * hourly pages:health-check then re-flags it as "Needs reconnect" forever.
 *
 * Real incident 2026-11-06: OT1-Pro FB + IG both stuck in reconnect loop
 * for a full day because granular_scopes.pages_messaging.target_ids only
 * listed the OTHER page (Brandk) the user had originally picked. Three
 * reconnect clicks did nothing because Meta never re-showed the picker.
 *
 * Removing auth_type=rerequest will reintroduce that silent failure mode.
 */
it('requests auth_type=rerequest on the FB-only OAuth URL to force picker re-display', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getConnectUrl();
    expect($url)->toContain('auth_type=rerequest');
});

it('requests auth_type=rerequest on the FB+IG combined OAuth URL to force picker re-display', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getInstagramViaFacebookConnectUrl();
    expect($url)->toContain('auth_type=rerequest');
});
