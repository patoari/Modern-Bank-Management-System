<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', User::class);
        $users = User::with(['roles', 'staff.branch', 'customer'])
            ->when($request->search, fn($q) => $q->where('email', 'like', "%{$request->search}%")
                                                   ->orWhere('phone', 'like', "%{$request->search}%"))
            ->when($request->user_type, fn($q) => $q->where('user_type', $request->user_type))
            ->when($request->status,    fn($q) => $q->where('status', $request->status))
            ->whereNull('deleted_at')
            ->latest()->paginate($request->integer('per_page', 15));
        return response()->json(['success' => true, 'data' => $users]);
    }

    public function show(User $user): JsonResponse
    {
        $this->authorize('view', $user);
        $user->load(['roles.permissions', 'staff.branch', 'customer']);
        return response()->json(['success' => true, 'data' => $user]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', User::class);
        $data = $request->validate([
            'email'      => 'required|email|unique:users',
            'phone'      => 'nullable|string|max:20|unique:users',
            'password'   => 'required|string|min:10',
            'user_type'  => 'required|in:customer,staff,admin',
            'role'       => 'required|string|exists:roles,name',
        ]);

        $user = User::create([
            'uuid'          => Str::uuid(),
            'email'         => $data['email'],
            'phone'         => $data['phone'] ?? null,
            'password_hash' => Hash::make($data['password']),
            'user_type'     => $data['user_type'],
            'status'        => 'active',
            'force_password_change' => true,
        ]);
        $user->assignRole($data['role']);

        return response()->json(['success' => true, 'message' => 'User created.', 'data' => $user->load('roles')], 201);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $this->authorize('update', $user);
        $data = $request->validate([
            'phone'      => 'sometimes|string|max:20',
            'status'     => 'sometimes|in:active,inactive,suspended',
            'role'       => 'sometimes|string|exists:roles,name',
        ]);

        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
            unset($data['role']);
        }
        $user->update($data);
        return response()->json(['success' => true, 'message' => 'User updated.', 'data' => $user->load('roles')]);
    }

    public function destroy(User $user): JsonResponse
    {
        $this->authorize('delete', $user);
        $user->delete();
        return response()->json(['success' => true, 'message' => 'User deleted.']);
    }

    public function assignRole(Request $request, User $user): JsonResponse
    {
        $this->authorize('assignRoles', $user);
        $data = $request->validate(['role' => 'required|string|exists:roles,name']);
        $user->syncRoles([$data['role']]);
        return response()->json(['success' => true, 'message' => 'Role assigned.', 'data' => $user->load('roles')]);
    }

    public function roles(): JsonResponse
    {
        $roles = Role::with('permissions')->get();
        return response()->json(['success' => true, 'data' => $roles]);
    }
}
