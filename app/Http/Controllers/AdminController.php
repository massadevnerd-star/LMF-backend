<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminController extends Controller
{
    // List all users with their roles
    public function index()
    {
        $users = User::with('roles')->get();
        return response()->json($users);
    }

    // Assign Role to User
    public function assignRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|string|exists:roles,name',
        ]);

        $user = User::findOrFail($id);
        $roleName = $request->input('role');

        // Sync roles (replaces existing roles) or assignRole (adds to existing)
        // Usually in a simple RBAC system for this type of app, a user might just have one main role or we might want to add.
        // Let's use syncRoles to ensure they have exactly this role set, OR logic depending on requirement.
        // User asked "assign or remove role", usually sync is safer to avoid accumulation if single-role-concept.
        // However, Spatie allows multiple. Let's assume we want to SET the roles.

        $user->syncRoles([$roleName]);

        return response()->json([
            'message' => "Role '{$roleName}' assigned successfully to user {$user->name}",
            'user' => $user->load('roles')
        ]);
    }
}
