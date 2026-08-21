<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use App\Support\LogSanitizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class AuditService
{
    public function __construct(private readonly LogSanitizer $sanitizer) {}

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
            'action' => $this->sanitizer->sanitizeMessage($action),
            'auditable_type' => $auditable?->getMorphClass(),
            'auditable_id' => $auditable?->getKey(),
            'before' => $this->sanitize($before),
            'after' => $this->sanitize($after),
            'ip_address' => $request ? $this->sanitizer->sanitizeMessage((string) $request->ip()) : null,
            'user_agent' => $request ? $this->sanitizer->sanitizeMessage((string) $request->userAgent()) : null,
        ]);
    }

    /** @return array<string, mixed>|null */
    private function sanitize(?array $data): ?array
    {
        if ($data === null) {
            return null;
        }

        $sanitized = $this->sanitizer->sanitize($data);

        return is_array($sanitized) ? $this->removeRedactedValues($sanitized) : null;
    }

    /** @param array<string|int, mixed> $data
     * @return array<string|int, mixed>
     */
    private function removeRedactedValues(array $data): array
    {
        foreach ($data as $key => $value) {
            if ($value === '[REDACTED]') {
                unset($data[$key]);
            } elseif (is_array($value)) {
                $data[$key] = $this->removeRedactedValues($value);
            }
        }

        return $data;
    }
}
