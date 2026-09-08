<?php

declare(strict_types=1);

use App\Services\Platforms\FacebookPlatform;

uses(Tests\TestCase::class);

/*
 * pages_manage_engagement scope requires TWO things to work:
 *   1. Present in the OAuth URL (this file's assertions)
 *   2. Checked in the Facebook Login for Business config on
 *      developers.facebook.com/apps/1469090344742803/business-login/configurations
 *
 * If (2) is not done, Meta rejects OAuth with a misleading
 * "Invalid Scopes: pages_read_user_content" error. The FLfB config was fixed
 * on 2026-09-08 to include the scope. Do not remove the scope from the URL
 * without also unchecking it in the FLfB config (or Meta will silently accept
 * the wrong permission set for consent).
 */

it('requests pages_manage_engagement in the FB-only OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getConnectUrl();
    expect($url)->toContain('pages_manage_engagement');
    expect($url)->toContain('pages_messaging');
    expect($url)->toContain('pages_read_engagement');
});

it('requests pages_manage_engagement in the FB+IG combined OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getInstagramViaFacebookConnectUrl();
    expect($url)->toContain('pages_manage_engagement');
    expect($url)->toContain('instagram_manage_comments');
    expect($url)->toContain('pages_messaging');
});
