<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Page extends Model
{
    protected $fillable = [
        'connected_account_id',
        'team_id',
        'platform',
        'platform_page_id',
        'name',
        'avatar',
        'page_access_token',
        'category',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'page_access_token' => 'encrypted',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // Invariant: at most one active Page row per (platform_page_id, platform).
        // When a row becomes active, deactivate any other active sibling sharing the
        // same Meta entity and record the takeover in page_transfers.
        // Without this, webhook delivery is ambiguous when a user reconnects the same
        // Meta page to a different team.
        static::saved(function (Page $page): void {
            if (! $page->is_active || ! $page->platform_page_id) {
                return;
            }
            $becameActive = $page->wasChanged('is_active') || $page->wasRecentlyCreated;
            if (! $becameActive) {
                return;
            }

            $siblings = static::query()
                ->where('platform', $page->platform)
                ->where('platform_page_id', $page->platform_page_id)
                ->where('is_active', true)
                ->where('id', '!=', $page->id)
                ->get();

            if ($siblings->isEmpty()) {
                return;
            }

            DB::transaction(function () use ($siblings, $page): void {
                foreach ($siblings as $sibling) {
                    PageTransfer::create([
                        'superseded_page_id'    => $sibling->id,
                        'superseded_by_page_id' => $page->id,
                        'from_team_id'          => $sibling->team_id,
                        'to_team_id'            => $page->team_id,
                        'actor_user_id'         => Auth::id(),
                        'reason'                => 'reconnect',
                        'snapshot'              => [
                            'platform'         => $sibling->platform,
                            'platform_page_id' => $sibling->platform_page_id,
                            'name'             => $sibling->name,
                        ],
                    ]);

                    $sibling->forceFill(['is_active' => false])->saveQuietly();

                    Log::info('Page ownership transferred', [
                        'superseded_page_id'    => $sibling->id,
                        'superseded_by_page_id' => $page->id,
                        'from_team_id'          => $sibling->team_id,
                        'to_team_id'            => $page->team_id,
                    ]);
                }
            });
        });
    }

    public function connectedAccount(): BelongsTo
    {
        return $this->belongsTo(ConnectedAccount::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    public function aiConfig(): HasOne
    {
        return $this->hasOne(AiConfig::class);
    }

    public function syncWindows(): HasMany
    {
        return $this->hasMany(PageSyncWindow::class);
    }
}
