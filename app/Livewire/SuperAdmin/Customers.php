<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Models\Message;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

class Customers extends Component
{
    #[Url]
    public string $sort = 'newest'; // newest | oldest | name

    public bool $showCreateModal = false;
    public string $companyName = '';
    public string $ownerName = '';
    public string $ownerEmail = '';
    public string $ownerPassword = '';

    public bool $showPasswordModal = false;
    public ?int $passwordUserId = null;
    public string $passwordUserName = '';
    public string $newPassword = '';

    public bool $showPagesModal = false;
    public string $pagesModalTeamName = '';
    public array $pagesModalPages = [];

    #[Computed]
    public function customers()
    {
        return Team::query()
            ->with(['owner', 'pages' => fn ($q) => $q->with('connectedAccount:id,metadata')
                ->orderByDesc('is_active')->orderBy('platform')->orderBy('name')])
            ->whereHas('owner', fn ($q) => $q->where('is_super_admin', false))
            ->withCount('pages')
            ->withCount('members')
            ->when(
                $this->sort === 'name',
                fn ($q) => $q->orderBy('name'),
                // Sign-up = when the owner's account was created.
                fn ($q) => $q->orderBy(
                    User::select('created_at')->whereColumn('users.id', 'teams.owner_id'),
                    $this->sort === 'oldest' ? 'asc' : 'desc',
                ),
            )
            ->get();
    }

    public function sortBy(string $sort): void
    {
        $this->sort = in_array($sort, ['newest', 'oldest', 'name'], true) ? $sort : 'newest';
        unset($this->customers, $this->summary);
    }

    /**
     * AI replies actually sent per team in the last 30 days (one grouped
     * query). Plan credits can be reset or comped; this is the real usage.
     *
     * @return array<int, int> team_id => count
     */
    #[Computed]
    public function aiReplies30d(): array
    {
        return Message::query()
            ->join('conversations', 'conversations.id', '=', 'messages.conversation_id')
            ->where('messages.sender_type', 'ai')
            ->where('messages.direction', 'outbound')
            ->where('messages.created_at', '>=', now()->subDays(30))
            ->groupBy('conversations.team_id')
            ->selectRaw('conversations.team_id, count(*) as total')
            ->pluck('total', 'conversations.team_id')
            ->map(fn ($n) => (int) $n)
            ->all();
    }

    /** Headline numbers for the summary strip. */
    #[Computed]
    public function summary(): array
    {
        $pages = $this->customers->flatMap->pages->where('is_active', true);

        return [
            'customers'      => $this->customers->count(),
            'with_connection' => $this->customers->filter(fn ($t) => $t->pages->contains('is_active', true))->count(),
            'active_pages'   => $pages->count(),
            'ai_replies_30d' => array_sum($this->aiReplies30d),
            'by_type'        => $pages->countBy(fn (Page $p) => $this->connectionType($p))->sortDesc()->all(),
        ];
    }

    /** Plan AI-credit allowance; null = unlimited. */
    public function aiCreditLimit(Team $team): ?int
    {
        $plan = config('stripe.plans.' . ($team->subscription_plan ?? 'free'), config('stripe.plans.free'));
        $limit = (int) ($plan['ai_credits'] ?? 0);

        return $limit === -1 ? null : $limit;
    }

    /** How the page is connected — the distinction matters for what it can receive. */
    public function connectionType(Page $page): string
    {
        return match ($page->platform) {
            'whatsapp'  => ! empty($page->connectedAccount?->metadata['gateway_mode']) ? __('WhatsApp QR') : __('WhatsApp Cloud API'),
            'instagram' => ($page->metadata['auth_type'] ?? null) === 'instagram_business' ? __('Instagram (Direct login)') : __('Instagram (via Meta)'),
            'facebook'  => __('Messenger'),
            'telegram'  => __('Telegram'),
            'email'     => __('Email'),
            default     => ucfirst((string) $page->platform),
        };
    }

    public function openCreateModal(): void
    {
        $this->reset(['companyName', 'ownerName', 'ownerEmail', 'ownerPassword']);
        $this->showCreateModal = true;
    }

    public function createCustomer(): void
    {
        $this->validate([
            'companyName'   => ['required', 'string', 'max:255'],
            'ownerName'     => ['required', 'string', 'max:255'],
            'ownerEmail'    => ['required', 'email', 'unique:users,email'],
            'ownerPassword' => ['required', Password::min(8)],
        ]);

        DB::transaction(function () {
            $user = User::create([
                'name'              => $this->ownerName,
                'email'             => $this->ownerEmail,
                'password'          => Hash::make($this->ownerPassword),
                'email_verified_at' => now(),
                'is_super_admin'    => false,
            ]);

            $team = Team::create([
                'name'     => $this->companyName,
                'slug'     => Str::slug($this->companyName) . '-' . Str::lower(Str::random(6)),
                'owner_id' => $user->id,
            ]);

            $team->members()->attach($user->id, [
                'role'        => 'admin',
                'permissions' => json_encode([]),
            ]);

            $user->update(['current_team_id' => $team->id]);
        });

        $this->showCreateModal = false;
        unset($this->customers, $this->summary);

        session()->flash('success', "Customer \"{$this->companyName}\" provisioned. Share the login with {$this->ownerEmail}.");
    }

    public function openPasswordModal(int $userId): void
    {
        $user = User::find($userId);
        if (! $user || $user->isSuperAdmin()) {
            return;
        }

        $this->passwordUserId = $userId;
        $this->passwordUserName = $user->name;
        $this->newPassword = '';
        $this->showPasswordModal = true;
    }

    public function resetPassword(): void
    {
        $this->validate([
            'newPassword' => ['required', Password::min(8)],
        ]);

        $user = User::find($this->passwordUserId);
        if (! $user || $user->isSuperAdmin()) {
            $this->showPasswordModal = false;
            return;
        }

        $user->update(['password' => Hash::make($this->newPassword)]);

        $this->showPasswordModal = false;
        session()->flash('success', "Password reset for \"{$user->name}\".");
    }

    public function openPagesModal(int $teamId): void
    {
        $team = Team::find($teamId);
        if (! $team) {
            return;
        }

        $this->pagesModalTeamName = $team->name;
        $this->pagesModalPages = Page::where('team_id', $team->id)
            ->orderBy('platform')
            ->orderBy('name')
            ->get(['name', 'platform', 'is_active'])
            ->map(fn ($p) => [
                'name'      => $p->name,
                'platform'  => $p->platform,
                'is_active' => (bool) $p->is_active,
            ])
            ->all();

        $this->showPagesModal = true;
    }

    public function deleteCustomer(int $teamId): void
    {
        $team = Team::with('owner')->find($teamId);
        if (! $team || $team->owner?->isSuperAdmin()) {
            return;
        }

        $name = $team->name;

        DB::transaction(function () use ($team) {
            $ownerId = $team->owner_id;
            Page::where('team_id', $team->id)->update(['is_active' => false]);
            $team->delete();
            $owner = User::find($ownerId);
            if ($owner && ! $owner->isSuperAdmin()) {
                $owner->delete();
            }
        });

        unset($this->customers, $this->summary);
        session()->flash('success', "Customer \"{$name}\" deleted.");
    }

    public function render()
    {
        return view('livewire.super-admin.customers')
            ->layout('layouts.app', ['title' => 'Customers']);
    }
}
