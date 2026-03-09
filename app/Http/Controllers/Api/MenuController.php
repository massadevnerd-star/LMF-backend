<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MenuController extends Controller
{
    /**
     * Get menu items visible to everyone (no roles required).
     * Public route - no authentication needed.
     */
    public function publicMenu()
    {
        $menuItems = MenuItem::whereDoesntHave('roles')
            ->orderBy('order')
            ->with('children')
            ->whereNull('parent_id')
            ->get();

        return response()->json($menuItems);
    }

    /**
     * Get the menu for the current authenticated user.
     */
    public function myMenu()
    {
        $user = Auth::user();
        $roles = $user->getRoleNames(); // Spatie helper

        // Fetch menu items that have at least one of the user's roles
        // or are public (no roles assigned? decision needed. let's assume all need roles for now or specific check)

        $menuItems = MenuItem::whereHas('roles', function ($query) use ($roles) {
            $query->whereIn('name', $roles);
        })
            ->orWhereDoesntHave('roles') // Optional: items with no roles are visible to everyone? Or admin only? Let's say visible to all.
            ->orderBy('order')
            ->with(['children', 'roles'])
            ->whereNull('parent_id') // Get root items
            ->get();

        return response()->json($menuItems);
    }

    /**
     * Admin: Get all menu items with their roles.
     */
    public function index()
    {
        $menuItems = MenuItem::with('roles')->orderBy('order')->get();
        return response()->json($menuItems);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'label' => 'required|string',
            'route' => 'nullable|string',
            'icon' => 'nullable|string',
            'order' => 'integer',
            'parent_id' => 'nullable|exists:menus,id',
            'roles' => 'array', // Array of role IDs
        ]);

        $menuItem = MenuItem::create($validated);

        if (isset($validated['roles'])) {
            $menuItem->roles()->sync($validated['roles']);
        }

        return response()->json($menuItem->load('roles'), 201);
    }

    public function update(Request $request, $id)
    {
        $menuItem = MenuItem::findOrFail($id);

        $validated = $request->validate([
            'label' => 'string',
            'route' => 'nullable|string',
            'icon' => 'nullable|string',
            'order' => 'integer',
            'parent_id' => 'nullable|exists:menus,id',
            'roles' => 'array',
        ]);

        $menuItem->update($validated);

        if (isset($validated['roles'])) {
            $menuItem->roles()->sync($validated['roles']);
        }

        return response()->json($menuItem->load('roles'));
    }

    public function destroy($id)
    {
        $menuItem = MenuItem::findOrFail($id);
        $menuItem->delete();
        return response()->json(['message' => 'Menu item deleted']);
    }
}
