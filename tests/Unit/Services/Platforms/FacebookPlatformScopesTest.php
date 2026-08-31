<?php

declare(strict_types=1);

use App\Services\Platforms\FacebookPlatform;

uses(Tests\TestCase::class);

it('requests pages_manage_engagement in the FB-only OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getConnectUrl();
    expect($url)->toContain('pages_manage_engagement');
});

it('requests pages_manage_engagement in the FB+IG combined OAuth URL', function () {
    config(['services.meta.app_id' => 'test-app', 'services.meta.app_secret' => 'test-secret']);
    $url = (new FacebookPlatform())->getInstagramViaFacebookConnectUrl();
    expect($url)->toContain('pages_manage_engagement');
    expect($url)->toContain('instagram_manage_comments');
});
