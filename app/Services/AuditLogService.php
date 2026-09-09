<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class AuditLogService
{
    public function record(?User $actor, string $action, Model $subject, array $metadata = []): AuditLog
    {
        unset($metadata['password'], $metadata['token'], $metadata['encrypted_token'], $metadata['token_hash']);

        return AuditLog::query()->create([
            'user_id' => $actor?->id,
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'metadata' => $metadata === [] ? null : $metadata,
            'created_at' => now(),
        ]);
    }
}
