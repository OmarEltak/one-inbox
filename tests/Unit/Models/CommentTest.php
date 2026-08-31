<?php

declare(strict_types=1);

use App\Models\Comment;
use App\Models\Page;
use App\Models\PagesPost;

uses(Tests\TestCase::class, Illuminate\Foundation\Testing\RefreshDatabase::class);

it('belongs to a page and a pages_post', function () {
    $comment = Comment::factory()->create();

    expect($comment->page)->toBeInstanceOf(Page::class);
    expect($comment->pagesPost)->toBeInstanceOf(PagesPost::class);
});

it('exposes decision constants', function () {
    expect(Comment::DECISION_REPLIED)->toBe('replied');
    expect(Comment::DECISION_RATE_LIMITED)->toBe('rate_limited');
    expect(Comment::DECISION_FILTERED_MODE)->toBe('filtered_mode');
    expect(Comment::DECISION_ERROR_GRAPH_API)->toBe('error_graph_api');
});

it('casts graph_error as array', function () {
    $comment = Comment::factory()->create([
        'graph_error' => ['code' => 100, 'message' => 'test'],
    ]);
    $comment->refresh();

    expect($comment->graph_error)->toBe(['code' => 100, 'message' => 'test']);
});
