<?php

declare(strict_types=1);

use App\Models\Page;
use App\Models\PagesPost;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('belongs to a page', function () {
    $page = Page::factory()->create();
    $post = PagesPost::factory()->create(['page_id' => $page->id]);

    expect($post->page->id)->toBe($page->id);
});

it('casts datetime fields', function () {
    $post = PagesPost::factory()->create();

    expect($post->created_at_platform)->toBeInstanceOf(\DateTimeInterface::class);
    expect($post->first_seen_at)->toBeInstanceOf(\DateTimeInterface::class);
});
