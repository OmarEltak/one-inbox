<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Computed;
use Livewire\Component;

class AdminManagement extends Component
{
    public const PERMISSIONS = [
        'dashboard'     => 'Dashboard',
        'inbox'         => 'Inbox',
        'contacts'      => 'Contacts',
        'analytics'     => 'Analytics',
        'connections'   => 'Connections',
        'ai-chat'       => 'AI Chat',
        'ai-settings'   => 'AI Settings',
        'manage-admins' => 'Manage Admins',
        'ai-control'    => 'AI Control (Pause/Resume AI)',
    ];

    // Create form
    public bool $showCreateModal = false;
    public string $createName = '';
    public string $createEmail = '';
    public string $createPassword = '';
    public array $createPermissions = [];

    // Edit form
    public bool $showEditModal = false;
    public ?int $editingAdminId = null;
    public string $editingAdminName = '';
    public array $editPermissions = [];

    // Password reset form
    public bool $showPasswordModal = false;
    public ?int $passwordAdminId = null;
    public string $passwordAdminName = '';
    public string $newPassword = '';

    #[Computed]
    public function teamAdmins()
    {
        $team = Auth::user()->currentTeam;

        return $team->members()
            ->where('users.id', '!=', $team->owner_id)
            ->get()
            ->map(function ($user) {
                $raw = $user->pivot->permissions;
                $user->pivot->permissions = is_string($raw) ? (json_decode($raw, true) ?? []) : ($raw ?? []);

                return $user;
            });
    }

    public function openCreateModal(): void
    {
        $this->resetCreateForm();
        $this->showCreateModal = true;
    }

    public function createAdmin(): void
    {
        $this->validate([
            'createName'        => 'required|string|max:255',
            'createEmail'       => 'required|email|unique:users,email',
            'createPassword'    => ['required', Password::min(8)],
            'createPermissions' => 'array',
        ]);

        $team = Auth::user()->currentTeam;

        $user = User::create([
            'name'              => $this->createName,
            'email'             => $this->createEmail,
            'password'          => Hash::make($this->createPassword),
            'email_verified_at' => now(),
            'current_team_id'   => $team->id,
        ]);

        $team->members()->attach($user->id, [
            'role'        => 'admin',
            'permissions' => json_encode($this->createPermissions),
        ]);

        $this->showCreateModal = false;
        $this->resetCreateForm();
        unset($this->teamAdmins);

        session()->flash('success', "Admin \"{$user->name}\" created successfully.");
    }

    public function openEditModal(int $adminId): void
    {
        $team = Auth::user()->currentTeam;
        $admin = $team->members()->where('users.id', $adminId)->first();

        if (! $admin) {
            return;
        }

        $raw = $admin->pivot->permissions;
        $this->editPermissions = is_string($raw) ? (json_decode($raw, true) ?? []) : ($raw ?? []);
        $this->editingAdminId = $adminId;
        $this->editingAdminName = $admin->name;
        $this->showEditModal = true;
    }

    public function savePermissions(): void
    {
        $team = Auth::user()->currentTeam;

        $team->members()->updateExistingPivot($this->editingAdminId, [
            'permissions' => json_encode($this->editPermissions),
        ]);

        $this->showEditModal = false;
        unset($this->teamAdmins);

        session()->flash('success', "Permissions updated for \"{$this->editingAdminName}\".");
    }

    public function openPasswordModal(int $adminId): void
    {
        $team = Auth::user()->currentTeam;
        $admin = $team->members()->where('users.id', $adminId)->first();

        if (! $admin) {
            return;
        }

        $this->passwordAdminId = $adminId;
        $this->passwordAdminName = $admin->name;
        $this->newPassword = '';
        $this->showPasswordModal = true;
    }

    public function resetPassword(): void
    {
        $this->validate([
            'newPassword' => ['required', Password::min(8)],
        ]);

        $team = Auth::user()->currentTeam;
        $admin = $team->members()->where('users.id', $this->passwordAdminId)->first();

        if (! $admin) {
            $this->showPasswordModal = false;

            return;
        }

        $admin->update(['password' => Hash::make($this->newPassword)]);

        $this->showPasswordModal = false;
        session()->flash('success', "Password reset for \"{$admin->name}\".");
    }

    public function deleteAdmin(int $adminId): void
    {
        $team = Auth::user()->currentTeam;
        $admin = $team->members()->where('users.id', $adminId)->first();

        if (! $admin) {
            return;
        }

        $name = $admin->name;

        // Remove from team
        $team->members()->detach($adminId);

        // Delete the user account entirely
        $admin->delete();

        unset($this->teamAdmins);
        session()->flash('success', "Admin \"{$name}\" removed.");
    }

    private function resetCreateForm(): void
    {
        $this->createName = '';
        $this->createEmail = '';
        $this->createPassword = '';
        $this->createPermissions = [];
    }

    public function render()
    {
        return view('livewire.settings.admin-management')
            ->layout('layouts.app', ['title' => 'Admin Management']);
    }
}
