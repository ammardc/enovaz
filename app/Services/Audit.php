<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    /**
     * Catat aktivitas penting. Jangan pernah memasukkan password, token, cookie, atau kredensial ke $meta.
     */
    public static function log(string $action, ?Model $target = null, array $meta = [], ?int $actorId = null): void
    {
        AuditLog::create([
            'actor_user_id' => $actorId ?? auth()->id(),
            'action' => $action,
            'target_type' => $target ? $target->getTable() : null,
            'target_id' => $target?->getKey(),
            'ip' => app()->runningInConsole() ? null : request()->ip(),
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
