<?php

declare(strict_types=1);

use App\Services\Platforms\FacebookPlatform;

uses(Tests\TestCase::class);

/*
 * pages_manage_engagement was intentionally REMOVED on 2026-09-07 because it
 * triggered Meta to match a Facebook Login for Business config on the app side
 * that includes the deprecated pages_read_user_content scope. Meta rejected every
 * OAuth request with "Invalid Scopes: pages_read_user_content" (naming the
 * config-injected deprecated scope, not the trigger scope), breaking connect flow
 * for all customers. See git commit revert and journal.
 *
 * These tests assert the scope is currently ABSENT. When the FLfB config on Meta
 * Dev Console is cleaned and pages_manage_engagement is re-added, flip these
 * assertions back to toContain.
 */

it('does NOT request pages_manage_engagement in the FB-only OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getConnectUrl();
    expect($url)->not->toContain('pages_manage_engagement');
    // Regression safety: existing required scopes must still be present.
    expect($url)->toContain('pages_messaging');
    expect($url)->toContain('pages_read_engagement');
});

it('does NOT request pages_manage_engagement in the FB+IG combined OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getInstagramViaFacebookConnectUrl();
    expect($url)->not->toContain('pages_manage_engagement');
    expect($url)->toContain('instagram_manage_comments');
    expect($url)->toContain('pages_messaging');
});
