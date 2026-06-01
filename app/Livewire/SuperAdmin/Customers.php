<?php

declare(strict_types=1);

namespace App\Livewire\SuperAdmin;

use App\Models\Page;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Customers extends Component
{
    public bool $showCreateModal = false;
    public string $companyName = '';
    public string $ownerName = '';
    public string $ownerEmail = '';
    public string $ownerPassword = '';

    public bool $showPasswordModal = false;
    public ?int $passwordUserId = null;
    public string $passwordUserName = '';
    public string $newPassword = '';

    #[Computed]
    public function customers()
    {
        return Team::query()
            ->with('owner')
            ->whereHas('owner', fn ($q) => $q->where('is_super_admin', false))
            ->withCount('pages')
            ->withCount('members')
            ->orderBy('name')
            ->get();
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
        unset($this->customers);

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

        unset($this->customers);
        session()->flash('success', "Customer \"{$name}\" deleted.");
    }

    public function render()
    {
        return view('livewire.super-admin.customers')
            ->layout('layouts.app', ['title' => 'Customers']);
    }
}
