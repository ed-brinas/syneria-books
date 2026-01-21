<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;

class TenantAccess extends Component
{
    use WithPagination;

    public $search = '';
    public $inviteEmail = '';
    
    // For the Approval Modal
    public $selectedUser = null;
    public $editingRole = 'Bookkeeper'; // Default to Maker role

    protected $rules = [
        'inviteEmail' => 'required|email|unique:users,email',
    ];

    public function mount()
    {
        // Security Gate: Only SuperAdministrator (Tenant Owner) can access this
        // Global roles are handled separately or via impersonation
        if (Auth::user()->role !== 'SuperAdministrator') {
            return abort(403, 'Unauthorized access.');
        }
    }

    // 1. Invite Flow
    public function inviteUser()
    {
        $this->validate();

        // Create the user in 'invited' state
        $newUser = User::create([
            'tenant_id' => Auth::user()->tenant_id,
            'email' => $this->inviteEmail,
            'status' => 'invited', // Special status indicating they haven't logged in yet
            'role' => 'Bookkeeper', // Default role (The Maker)
            'email_hash' => hash_hmac('sha256', $this->inviteEmail, config('app.key')),
        ]);

        // Send Email logic (Placeholder)
        // Mail::to($this->inviteEmail)->send(new \App\Mail\UserInvited($newUser));

        $this->reset('inviteEmail');
        session()->flash('success', 'Invitation sent to ' . $newUser->email);
    }

    // 2. Open Approval/Edit Modal
    public function manageUser($userId)
    {
        $this->selectedUser = User::where('id', $userId)
            ->where('tenant_id', Auth::user()->tenant_id)
            ->firstOrFail();
            
        $this->editingRole = $this->selectedUser->role;
        
        $this->dispatch('open-modal'); // Trigger JS modal
    }

    // 3. Approve and Save Access
    public function saveAccess()
    {
        if (!$this->selectedUser) return;

        // Validation: Prevent removing the last SuperAdministrator
        // If the user currently has the 'SuperAdministrator' role and we are changing it to something else...
        if ($this->selectedUser->role === 'SuperAdministrator' && $this->editingRole !== 'SuperAdministrator') {
            
            // Count how many ACTIVE SuperAdministrators exist for this tenant
            $adminCount = User::where('tenant_id', Auth::user()->tenant_id)
                ->where('role', 'SuperAdministrator')
                ->where('status', 'active')
                ->count();

            // If this is the last one (or zero), prevent the change
            if ($adminCount <= 1) {
                $this->addError('role', 'Organization must have at least one SuperAdministrator. Assign another user as SuperAdministrator before changing this role.');
                return;
            }
        }

        // Update the user's role (overwriting any previous role) and status
        $this->selectedUser->update([
            'role' => $this->editingRole,
            'status' => 'active', // approving them makes them active
        ]);

        $this->reset(['selectedUser', 'editingRole']);
        $this->dispatch('close-modal');
        session()->flash('success', 'User access updated successfully.');
    }

    // 4. Suspend User
    public function toggleStatus($userId)
    {
        $user = User::where('id', $userId)->where('tenant_id', Auth::user()->tenant_id)->first();
        
        if ($user->id === Auth::id()) return; // Cannot suspend self

        $user->status = ($user->status === 'active') ? 'suspended' : 'active';
        $user->save();
    }

    public function render()
    {
        $users = User::where('tenant_id', Auth::user()->tenant_id)
            ->where(function($q) {
                $q->where('email', 'like', '%'.$this->search.'%')
                  ->orWhere('first_name', 'like', '%'.$this->search.'%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('settings.users', [
            'users' => $users
        ])->layout('layouts.app', ['title' => 'Tenant Access & Roles']);
    }
}