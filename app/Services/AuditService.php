<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'current_password', 'remember_token',
        'token', 'secret', 'cookie', 'authorization',
    ];

    public function record(
        string $action,
        ?Model $auditable = null,
        ?array $before = null,
        ?array $after = null,
        ?User $user = null,
        ?Request $request = null,
    ): AuditLog {
        $request ??= app()->bound('request') ? request() : null;

        return AuditLog::create([
            'user_id' => $user?->getKey() ?? auth()->id(),
            'action' => $action,
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'before' => $this->sanitize($before),
            'after' => $this->sanitize($after),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    /** @return array<string, mixed>|null */
    private function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                unset($data[$key]);

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->sanitize($value);
            }
        }

        return $data;
    }
}
