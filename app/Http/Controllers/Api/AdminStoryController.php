<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Story;
use Illuminate\Http\Request;

class AdminStoryController extends Controller
{
    public function index(Request $request)
    {
        $query = Story::with('user:id,name,email');

        // Filter by Status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by Title
        if ($request->has('search')) {
            $search = $request->search;
            $query->where('story_subject', 'like', "%{$search}%");
        }

        // Sort by Credits Used (descending by default for "Top Spenders")
        if ($request->has('sort_by_credits')) {
            $query->orderBy('credits_used', 'desc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $stories = $query->paginate(20);

        return response()->json($stories);
    }

    public function show($id)
    {
        $story = Story::with('user')->findOrFail($id);
        return response()->json($story);
    }

    public function destroy($id)
    {
        $story = Story::findOrFail($id);
        $story->delete();
        return response()->json(['message' => 'Story deleted by admin']);
    }
}
