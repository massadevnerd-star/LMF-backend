<?php

namespace App\Http\Controllers;

use App\Models\Child;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChildController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(Auth::user()->children); // Assuming User model has children() relation defined, or we fetch manually
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nickname' => 'required|string|max:255',
            'birthdate' => 'nullable|date',
            'type' => 'required|string',
            'avatar' => 'nullable|string'
        ]);

        $child = Auth::user()->children()->create($validated);

        return response()->json($child, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $child = Auth::user()->children()->findOrFail($id);
        return response()->json($child);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $child = Auth::user()->children()->findOrFail($id);

        $validated = $request->validate([
            'nickname' => 'sometimes|required|string|max:255',
            'birthdate' => 'nullable|date',
            'type' => 'sometimes|required|string',
            'avatar' => 'nullable|string'
        ]);

        $child->update($validated);

        return response()->json($child);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $child = Auth::user()->children()->findOrFail($id);
        $child->delete();

        return response()->json(['message' => 'Profile deleted']);
    }
}
