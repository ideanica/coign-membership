<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;

class ActivityRecorder
{
    public function record(User $actor, string $area, string $action, string $routeName, array $targets = []): void
    {
        ActivityLog::create([
            'actor_id' => $actor->id,
            'actor_name' => $actor->name,
            'actor_email' => $actor->email,
            'actor_role' => $actor->isSuperAdmin() ? 'Superadmin' : 'Admin',
            'area' => $area,
            'action' => $action,
            'route_name' => $routeName,
            'targets' => $targets,
            'occurred_at' => now(),
        ]);
    }
}
