<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogActivity
{
    /**
     * Handle an incoming request and log all activity
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Skip logging for certain routes (health checks, etc.)
        $excludedPaths = ['api/health', 'sanctum/csrf-cookie'];
        if (in_array($request->path(), $excludedPaths)) {
            return $response;
        }

        try {
            // Get controller and method info
            $route = $request->route();
            $actionName = $route ? $route->getActionName() : 'Closure';

            // Parse controller@method format
            $controllerMethod = $actionName;
            if (strpos($actionName, '@') !== false) {
                [$controller, $method] = explode('@', $actionName);
                $controllerMethod = class_basename($controller) . '@' . $method;
            }

            // Log the activity
            activity()
                ->causedBy(auth()->user())
                ->withProperties([
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
                    'path' => $request->path(),
                    'route_name' => $route?->getName(),
                    'controller_method' => $controllerMethod,
                    'params' => $request->except([
                        'password',
                        'password_confirmation',
                        'pin',
                        'old_pin',
                        'new_pin',
                        'new_pin_confirmation',
                        'reset_code'
                    ]),
                    'status_code' => $response->getStatusCode(),
                ])
                ->log($request->method() . ' ' . $request->path());
        } catch (\Exception $e) {
            // Don't break the request if logging fails
            \Log::error('Activity logging failed: ' . $e->getMessage());
        }

        return $response;
    }
}
