<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StoryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $rules = [
            'output' => 'required', // Drafts still need output content
            'creationMode' => 'nullable|string|in:manual,ai',
            'status' => 'nullable|string|in:draft,published',
            // Allow metadata saving for drafts too
            'storySubject' => 'nullable|string',
            'storyType' => 'nullable|string',
            'ageGroup' => 'nullable|string',
            'imageStyle' => 'nullable|string',
        ];

        // Conditional validation: Full stories need all fields
        if ($request->input('status') !== 'draft') {
            $rules['storySubject'] = 'required|string';
            $rules['storyType'] = 'required|string';
            $rules['ageGroup'] = 'required|string';
            $rules['imageStyle'] = 'required|string';
        }

        $validated = $request->validate($rules);

        $user = $request->user();

        $story = $user->stories()->create([
            'story_subject' => $validated['storySubject'] ?? null,
            'story_type' => $validated['storyType'] ?? null,
            'age_group' => $validated['ageGroup'] ?? null,
            'image_style' => $validated['imageStyle'] ?? null,
            'creation_mode' => $validated['creationMode'] ?? 'manual',
            'output' => $validated['output'],
            'status' => $validated['status'] ?? 'published',
            'cover_image' => $validated['output']['coverImage'] ?? null,
        ]);

        if (($validated['status'] ?? 'published') === 'published') {
            // Increment user credits (usage count) only on publish? Or always?
            // Assuming usage is counted on creation for now.
            $cost = (int) env('STORY_COST', 1);
            $user->increment('credits', $cost);
        }

        if ($request->has('assigned_children')) {
            $story->children()->sync($request->input('assigned_children'));
        }

        return response()->json($story->load('children'), 201);
    }

    public function update(Request $request, $id): JsonResponse
    {
        $story = $request->user()->stories()->findOrFail($id);

        $rules = [
            'output' => 'nullable',
            'creationMode' => 'nullable|string|in:manual,ai',
            'status' => 'nullable|string|in:draft,published',
            // Allow metadata saving for drafts too
            'storySubject' => 'nullable|string',
            'storyType' => 'nullable|string',
            'ageGroup' => 'nullable|string',
            'imageStyle' => 'nullable|string',
        ];

        if ($request->input('status') === 'published') {
            $rules['storySubject'] = 'required|string';
            $rules['storyType'] = 'required|string';
            $rules['ageGroup'] = 'required|string';
            $rules['imageStyle'] = 'required|string';
        }

        $validated = $request->validate($rules);

        $story->update([
            'story_subject' => $validated['storySubject'] ?? $story->story_subject,
            'story_type' => $validated['storyType'] ?? $story->story_type,
            'age_group' => $validated['ageGroup'] ?? $story->age_group,
            'image_style' => $validated['imageStyle'] ?? $story->image_style,
            'creation_mode' => $validated['creationMode'] ?? $story->creation_mode,
            'output' => $validated['output'] ?? $story->output,
            'status' => $validated['status'] ?? $story->status,
            'cover_image' => ($validated['output']['coverImage'] ?? null) ?? $story->cover_image,
        ]);

        if ($request->has('assigned_children')) {
            $story->children()->sync($request->input('assigned_children'));
        }

        return response()->json($story->load('children'));
    }

    public function show(Request $request, $id): JsonResponse
    {
        $story = $request->user()->stories()->findOrFail($id);
        return response()->json($story);
    }

    public function index(Request $request): JsonResponse
    {
        // Fix for "Out of sort memory": Fetch IDs first (lightweight sort), then content, then sort in PHP.
        $status = $request->query('status');
        $childId = $request->query('child_id');

        $query = $request->user()->stories();

        if ($status) {
            $query->where('status', $status);
        } else {
            $query->where('status', 'published');
        }

        // Filter by Child Assignment if child_id is present
        if ($childId) {
            $query->where(function ($q) use ($childId) {
                // Show if assigned to this child
                $q->whereHas('children', function ($sq) use ($childId) {
                    $sq->where('child_id', $childId);
                })
                    // OR show if assigned to NO children (implicitly "For All")
                    ->orWhereDoesntHave('children');
            });
        }

        // 1. Get IDs sorted by date
        $ids = $query->orderBy('created_at', 'desc')->pluck('id');

        if ($ids->isEmpty()) {
            return response()->json([]);
        }

        // 2. Fetch full records (unordered)
        $stories = $request->user()->stories()
            ->with('children') // Load assignments
            ->whereIn('id', $ids)
            ->get();

        // 3. Sort in PHP to match ID order (or just re-sort by date)
        $stories = $stories->sortByDesc('created_at')->values();

        return response()->json($stories);
    }

    public function destroy(string $id)
    {
        $story = auth()->user()->stories()->findOrFail($id);
        $story->delete();

        return response()->json(null, 204);
    }

    public function testApi()
    {
        return response()->json('test');
    }

}
