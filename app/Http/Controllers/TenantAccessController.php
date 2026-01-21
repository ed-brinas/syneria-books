<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ActivityLog; // Added ActivityLog model
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class TenantAccessController extends Controller
{
    /**
     * Enforce RBAC: Only SuperAdministrators can access this controller.
     */
    public static function middleware(): array
    {
        return [
            function ($request, $next) {
                // Ensure user is authenticated and is a SuperAdministrator
                if (!Auth::check() || Auth::user()->role !== 'SuperAdministrator') {
                    abort(403, 'Unauthorized access.');
                }
                return $next($request);
            },
        ];
    }

    /**
     * Display the list of users.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');

        $users = User::where('tenant_id', Auth::user()->tenant_id)
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('settings.users.index', compact('users', 'search'));
    }

    /**
     * Invite a new user.
     */
    public function invite(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'position' => 'nullable|string|max:100',
            'role' => 'required|in:SuperAdministrator,Approver,Reviewer,Bookkeeper',
        ]);

        // Create the user in 'invited' state
        $newUser = new User();
        $newUser->tenant_id = Auth::user()->tenant_id;
        $newUser->first_name = $request->first_name;
        $newUser->last_name = $request->last_name;
        $newUser->name = $request->first_name . ' ' . $request->last_name; 
        $newUser->email = $request->email;
        $newUser->phone = $request->phone;
        $newUser->position = $request->position;
        $newUser->status = 'invited';
        $newUser->role = $request->role;
        $newUser->email_hash = hash_hmac('sha256', $request->email, config('app.key'));
        $newUser->save();

        // Placeholder for Email Notification
        // Mail::to($newUser->email)->send(new UserInvited($newUser));

        // Activity Log
        ActivityLog::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'action' => 'invited',
            'description' => "Invited user {$newUser->email} as {$newUser->role}",
            'subject_type' => User::class,
            'subject_id' => $newUser->id,
            'ip_address' => $request->ip(),
            'properties' => [
                'role' => $newUser->role,
                'email' => $newUser->email
            ],
        ]);

        return redirect()->route('settings.users.index')
            ->with('success', "Invitation sent to {$newUser->email}");
    }

    /**
     * Update a user's role.
     */
    public function update(Request $request, $id)
    {
        $user = User::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        $request->validate([
            'role' => 'required|in:SuperAdministrator,Approver,Reviewer,Bookkeeper',
        ]);

        $oldRole = $user->role;
        $newRole = $request->role;

        // Logic: Prevent removing the last SuperAdministrator
        if ($user->role === 'SuperAdministrator' && $newRole !== 'SuperAdministrator') {
            $adminCount = User::where('tenant_id', Auth::user()->tenant_id)
                ->where('role', 'SuperAdministrator')
                ->where('status', 'active')
                ->count();

            if ($adminCount <= 1) {
                return redirect()->back()->withErrors(['role' => 'Organization must have at least one SuperAdministrator.']);
            }
        }

        $user->role = $newRole;
        // If they were pending/invited, approving them makes them active
        if (in_array($user->status, ['invited', 'pending'])) {
            $user->status = 'active';
        }
        $user->save();

        // Activity Log
        ActivityLog::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'action' => 'updated_role',
            'description' => "Updated role for {$user->email} from {$oldRole} to {$newRole}",
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'ip_address' => $request->ip(),
            'properties' => [
                'old_role' => $oldRole,
                'new_role' => $newRole
            ],
        ]);

        return redirect()->back()->with('success', 'User access updated successfully.');
    }

    /**
     * Toggle status (Suspend/Activate).
     */
    public function toggleStatus($id)
    {
        $user = User::where('tenant_id', Auth::user()->tenant_id)->findOrFail($id);

        if ($user->id === Auth::id()) {
            return redirect()->back()->withErrors(['msg' => 'You cannot suspend yourself.']);
        }

        $oldStatus = $user->status;
        $user->status = ($user->status === 'active') ? 'suspended' : 'active';
        $user->save();

        $statusMsg = ucfirst($user->status);

        // Activity Log
        ActivityLog::create([
            'tenant_id' => Auth::user()->tenant_id,
            'user_id' => Auth::id(),
            'action' => 'toggled_status',
            'description' => "Changed status for {$user->email} from {$oldStatus} to {$user->status}",
            'subject_type' => User::class,
            'subject_id' => $user->id,
            'ip_address' => request()->ip(),
            'properties' => [
                'old_status' => $oldStatus,
                'new_status' => $user->status
            ],
        ]);

        return redirect()->back()->with('success', "User is now {$statusMsg}.");
    }
}