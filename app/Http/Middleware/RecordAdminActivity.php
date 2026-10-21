<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Services\ActivityRecorder;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RecordAdminActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isMethodSafe()) {
            return $next($request);
        }

        $actor = clone $request->user();
        $routeName = (string) $request->route()->getName();
        $parts = explode('.', $routeName);
        $area = $parts[1] ?? '';
        $targets = [];
        foreach ($request->route()->parameters() as $key => $value) {
            if ($value instanceof Model) {
                $targets[$key] = (string) $value->getKey();
            } elseif (is_scalar($value)) {
                $targets[$key] = mb_substr((string) $value, 0, 100);
            }
        }
        $response = $next($request);

        if ($area === 'activity-logs' || ! array_key_exists($area, ActivityLog::AREAS)
            || $response->getStatusCode() >= 400 || in_array('errors', $request->session()->get('_flash.new', []), true)) {
            return $response;
        }
        $action = end($parts) ?: 'update';
        if ($action === 'visibility') {
            $action = $request->boolean('is_active') ? 'publish' : 'hide';
        }
        if ($action === 'destroy' && in_array($area, ['members', 'services', 'catalog-categories'], true)) {
            $action = 'deactivate';
        } elseif ($action === 'destroy' && in_array($area, ['catalogs', 'statuses'], true)) {
            $action = 'archive';
        }
        if ($routeName === 'admin.settings.update') {
            $area = 'brand';
        } elseif ($routeName === 'admin.settings.profile.update') {
            $action = 'profile';
            $targets = ['user' => (string) $actor->id];
        } elseif ($routeName === 'admin.settings.password.update') {
            $action = 'password';
            $targets = ['user' => (string) $actor->id];
        }
        app(ActivityRecorder::class)->record($actor, $area, $action, $routeName, $targets);

        return $response;
    }
}
