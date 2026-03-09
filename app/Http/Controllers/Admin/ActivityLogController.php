<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Get paginated activity logs with filters
     */
    public function index(Request $request)
    {
        $query = Activity::with('causer', 'subject')
            ->latest();

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        // Filter by event type
        if ($request->has('event')) {
            $query->where('event', $request->event);
        }

        // Filter by description (search)
        if ($request->has('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // Filter by subject type (model)
        if ($request->has('subject_type')) {
            $query->where('subject_type', $request->subject_type);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by IP address
        if ($request->has('ip')) {
            $query->whereJsonContains('properties->ip', $request->ip);
        }

        // Filter by HTTP method
        if ($request->has('method')) {
            $query->whereJsonContains('properties->method', $request->method);
        }

        $perPage = $request->get('per_page', 50);
        $activities = $query->paginate($perPage);

        return response()->json($activities);
    }

    /**
     * Get single activity log details
     */
    public function show($id)
    {
        $activity = Activity::with('causer', 'subject')->findOrFail($id);

        return response()->json([
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'event' => $activity->event,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'causer_type' => $activity->causer_type,
            'causer_id' => $activity->causer_id,
            'causer' => $activity->causer,
            'subject' => $activity->subject,
            'properties' => $activity->properties,
            'batch_uuid' => $activity->batch_uuid,
            'created_at' => $activity->created_at,
            'updated_at' => $activity->updated_at,
        ]);
    }

    /**
     * Get activity statistics
     */
    public function stats(Request $request)
    {
        $dateFrom = $request->get('date_from', now()->subDays(30));
        $dateTo = $request->get('date_to', now());

        $query = Activity::whereBetween('created_at', [$dateFrom, $dateTo]);

        $stats = [
            'total_activities' => $query->count(),
            'unique_users' => $query->distinct('causer_id')->count('causer_id'),
            'by_event' => $query->groupBy('event')
                ->selectRaw('event, count(*) as count')
                ->pluck('count', 'event'),
            'by_subject_type' => $query->groupBy('subject_type')
                ->selectRaw('subject_type, count(*) as count')
                ->pluck('count', 'subject_type'),
            'by_day' => $query->groupBy(\DB::raw('DATE(created_at)'))
                ->selectRaw('DATE(created_at) as date, count(*) as count')
                ->pluck('count', 'date'),
        ];

        return response()->json($stats);
    }

    /**
     * Delete old activity logs
     */
    public function cleanup(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1'
        ]);

        $deleted = Activity::where('created_at', '<', now()->subDays($request->days))->delete();

        return response()->json([
            'message' => "Deleted {$deleted} old activity logs",
            'deleted_count' => $deleted
        ]);
    }
}
