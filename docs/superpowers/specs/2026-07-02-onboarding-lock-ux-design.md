# Onboarding Lock UX — Design Spec

**Date:** 2026-07-02
**Status:** Approved for implementation

## Problem

A new user who signs up lands on the dashboard with a full sidebar (Inbox, Contacts, Campaigns, Analytics, AI Chat, AI Settings, Connections). They have no signal that **Connections** is step 1. When they click any nav item, they see empty pages — or, in the case of `settings/ai/config`, a hard 500 because the AI config Livewire component throws when the team has no active pages.

Additionally, on the Connections page itself, clicking "Request connection" under Facebook or Instagram appears to do nothing. Root cause: the Livewire component dispatches a generic `open-modal` browser event, but the Flux 2.x `<flux:modal>` component does not listen for that event — Flux modals must be opened via `Flux::modal(name)->show()` from the server or `<flux:modal.trigger>` on the client.

## Goals

1. Guide a new user to Connections before they can wander elsewhere.
2. Prevent shared direct URLs (e.g., `/settings/ai/config`) from 500-ing for a not-yet-connected team.
3. Restore the FB/IG "Request connection" flow so admin-managed onboarding actually opens its modal.
4. Do all of the above with minimal per-render DB cost — one memoized query per request.

## Non-goals

- Landing page / login / connections page load-time optimization (separate task).
- Any change to `$metaVerified` behavior — it is a deliberate per-Meta-app gate, not per-team.
- Any change to the admin-onboarding review flow at `/super-admin/onboarding-requests`.

## Design

### 1. `Team::hasAnyConnection()` (memoized)

Add to `app/Models/Team.php`:

```php
private ?bool $hasAnyConnectionCache = null;

public function hasAnyConnection(): bool
{
    return $this->hasAnyConnectionCache ??= \App\Models\Page::query()
        ->where('team_id', $this->id)
        ->where('is_active', true)
        ->exists();
}
```

- **Why `is_active`:** A team with inactive/disconnected pages cannot see messages, so from a UX standpoint it counts as not connected.
- **Why `Page` not `ConnectedAccount`:** Pages exist for every platform (Meta, Telegram, WhatsApp, Email, WebChat). ConnectedAccount only exists for OAuth platforms.
- **Memoization:** One `EXISTS` query per Team instance per request. The sidebar and middleware share the same instance via `auth()->user()->currentTeam`.

### 2. `RequireConnection` middleware

New file: `app/Http/Middleware/RequireConnection.php`.

```php
public function handle(Request $request, Closure $next)
{
    $team = $request->user()?->currentTeam;

    // Super-admins bypass — they navigate teams for support.
    if ($request->user()?->isSuperAdmin()) {
        return $next($request);
    }

    if (! $team || ! $team->hasAnyConnection()) {
        return redirect()
            ->route('connections.index')
            ->with('info', 'Connect at least one platform to unlock this page.');
    }

    return $next($request);
}
```

Register in `bootstrap/app.php` middleware aliases as `'require.connection'`.

Apply to these routes in `routes/web.php` (append to existing `->middleware(...)` chains):

| Route name | Additional middleware |
|---|---|
| `inbox` | `require.connection` |
| `contacts.index` | `require.connection` |
| `campaigns.index` | `require.connection` |
| `content.index` | `require.connection` |
| `ai-chat` | `require.connection` |
| `analytics` | `require.connection` |
| `settings.ai` | `require.connection` |
| `settings.ai.config` | `require.connection` |

**Not gated:** `dashboard`, `connections.*`, `profile.*`, `settings.admins*`, `super-admin.*`, `teams.*`, all webhook/OAuth-callback routes.

### 3. Sidebar UI — locked state

In `resources/views/layouts/app/sidebar.blade.php`:

**Step A** — compute once at top of the nav block:

```php
$hasConnections = $team?->hasAnyConnection() ?? false;
$lockedRoutes = ['inbox', 'contacts.index', 'campaigns.index', 'content.index', 'analytics', 'ai-chat', 'settings.ai'];
```

**Step B** — extend each `$navItems[]` entry with a `'locked'` flag:

```php
$navItems[] = [
    'route' => 'contacts.index',
    'label' => 'Contacts',
    'icon'  => 'users',
    'match' => 'contacts*',
    'locked' => ! $hasConnections,
];
```

**Step C** — in the nav render loop, branch on `locked`:

- **Not locked:** existing `<a href wire:navigate>` markup.
- **Locked:** render as `<button type="button" @click="$dispatch('needs-connection')">`, add small lock icon on the right, apply `opacity-50 hover:opacity-70` to signal disabled-but-clickable.

**Inbox dropdown:** also lock. When `!$hasConnections`, the parent Inbox button becomes a locked button (dispatches `needs-connection`), the dropdown does not expand.

### 4. "Connect first" modal

Add once inside the `<body>`, after the `<flux:sidebar>` block:

```blade
<div x-data="{ open: false }"
     @needs-connection.window="open = true"
     x-show="open" x-cloak
     x-transition.opacity
     class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 backdrop-blur-sm">
    <div @click.outside="open = false"
         class="max-w-md w-[92vw] rounded-2xl border border-white/15 bg-[#0D0D1A] p-6 shadow-2xl">
        <h3 class="text-lg font-semibold text-white">Connect your first page</h3>
        <p class="mt-2 text-sm text-white/60">
            To see messages, contacts, and analytics, first connect at least one platform
            (Facebook, Instagram, WhatsApp, Telegram, or Email).
        </p>
        <div class="mt-5 flex justify-end gap-2">
            <button type="button" @click="open = false"
                    class="rounded-lg px-3 py-2 text-sm text-white/60 hover:text-white">
                Cancel
            </button>
            <a href="{{ route('connections.index') }}" wire:navigate @click="open = false"
               class="rounded-lg bg-purple-600 px-3 py-2 text-sm font-medium text-white hover:bg-purple-500">
                Go to Connections →
            </a>
        </div>
    </div>
</div>
```

### 5. Fix Flux modal wiring on Connections page

In `app/Livewire/Connections/Index.php`:

- Add `use Flux\Flux;` at top.
- Replace 3 dispatches:

```diff
- $this->dispatch('open-modal', name: 'onboarding-request');
+ Flux::modal('onboarding-request')->show();

- $this->dispatch('close-modal', name: 'onboarding-request');
+ Flux::modal('onboarding-request')->close();
```

Occurs in `openRequestForm()`, and twice in `submitOnboardingRequest()` (error branch + success branch).

## Verification

- **Connected team:** sidebar renders with no lock icons, no dimmed items, no modal on click. One `EXISTS` query in the request log per page load.
- **New team (0 pages):** all locked items dim, show lock icon, click opens the "Connect first" modal, "Go to Connections →" navigates to `connections.index`.
- **Direct URL to `/settings/ai/config` as a new team:** redirects to `connections.index` with the info flash. No 500.
- **Super-admin:** bypasses gate for any team (they need direct access for support).
- **FB "Request connection" click:** onboarding modal opens; submit creates `OnboardingRequest` row and closes modal.

## Files touched

1. `app/Models/Team.php` — add `hasAnyConnection()` + private cache property.
2. `app/Http/Middleware/RequireConnection.php` — new file.
3. `bootstrap/app.php` — register alias.
4. `routes/web.php` — append middleware on 8 routes.
5. `resources/views/layouts/app/sidebar.blade.php` — locked nav items + modal.
6. `app/Livewire/Connections/Index.php` — Flux modal API fix.
