<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|max:20480', // Max 20MB
        ]);

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            // Store in 'public/uploads'
            $path = $file->store('uploads', 'public');

            // Return relative path instead of absolute URL
            // Frontend will construct full URL using environment variables
            $relativePath = 'storage/' . $path;

            return response()->json([
                'url' => $relativePath,  // Now returns relative path
                'path' => $path
            ], 201);
        }

        return response()->json(['error' => 'No file uploaded'], 400);
    }
}
