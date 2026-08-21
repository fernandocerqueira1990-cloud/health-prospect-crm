<?php

namespace App\Support;

use Stringable;
use Throwable;

class LogSanitizer
{
    private const REDACTED = '[REDACTED]';

    /** @var list<string> */
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'current_password', 'db_password', 'redis_password',
        'mail_password', 'remember_token', 'token', 'api_token', 'access_token', 'refresh_token',
        'csrf_token', 'xsrf_token', '_token', 'secret', 'client_secret', 'app_key', 'api_key',
        'aws_access_key_id', 'aws_secret_access_key', 'cloudflare_api_token', 'cookie', 'cookies',
        'set_cookie', 'authorization', 'proxy_authorization', 'session', 'session_id', 'php_session_id',
        'file', 'file_content', 'original_data', 'normalized_data', 'dedup_data', 'execution_data',
    ];

    public function sanitizeMessage(string $message): string
    {
        $message = $this->neutralizeControls($message);
        $message = preg_replace('/\b(Bearer|Basic)\s+[A-Za-z0-9._~+\/-]+=*/i', '$1 '.self::REDACTED, $message) ?? $message;
        $sensitiveKey = '[a-z0-9_-]*(?:password|passwd|token|secret|api[_-]?key|app[_-]?key|authorization|cookie|session[_-]?id)[a-z0-9_-]*';
        $message = preg_replace(
            '/("'.$sensitiveKey.'")\s*:\s*"(?:\\\\.|[^"\\\\])*"/i',
            '$1:"'.self::REDACTED.'"',
            $message,
        ) ?? $message;
        $message = preg_replace(
            "/('".$sensitiveKey."')\\s*:\\s*'(?:\\\\.|[^'\\\\])*'/i",
            '$1:\''.self::REDACTED.'\'',
            $message,
        ) ?? $message;
        $message = preg_replace(
            '/\b('.$sensitiveKey.')\s*[:=]\s*("(?:\\\\.|[^"\\\\])*"|\'(?:\\\\.|[^\'\\\\])*\'|[^\s,;]+)/i',
            '$1='.self::REDACTED,
            $message,
        ) ?? $message;

        return mb_strimwidth($message, 0, 4096, '…', 'UTF-8');
    }

    public function sanitize(mixed $value, ?string $key = null, int $depth = 0): mixed
    {
        if ($key !== null && $this->isSensitiveKey($this->normalizeKey($key))) {
            return self::REDACTED;
        }

        if ($depth >= 8) {
            return '[MAX_DEPTH]';
        }

        if ($value instanceof Throwable) {
            return [
                'exception_class' => $value::class,
                'message' => $this->sanitizeMessage($value->getMessage()),
                'code' => $value->getCode(),
                'trace' => array_map(
                    fn (array $frame): array => array_filter([
                        'line' => $frame['line'] ?? null,
                        'class' => $frame['class'] ?? null,
                        'function' => $frame['function'],
                    ], fn (mixed $item): bool => $item !== null),
                    array_slice($value->getTrace(), 0, 30),
                ),
            ];
        }

        if (is_array($value)) {
            $sanitized = [];

            foreach ($value as $itemKey => $itemValue) {
                $sanitized[$itemKey] = $this->sanitize($itemValue, (string) $itemKey, $depth + 1);
            }

            return $sanitized;
        }

        if (is_string($value) || $value instanceof Stringable) {
            return $this->sanitizeMessage((string) $value);
        }

        if (is_object($value)) {
            return ['object_class' => $value::class];
        }

        return $value;
    }

    private function isSensitiveKey(string $key): bool
    {
        return in_array($key, self::SENSITIVE_KEYS, true)
            || str_ends_with($key, '_password')
            || str_ends_with($key, '_token')
            || str_ends_with($key, '_secret')
            || str_ends_with($key, '_api_key');
    }

    private function normalizeKey(string $key): string
    {
        $key = preg_replace('/(?<!^)[A-Z]/', '_$0', $key) ?? $key;

        return strtolower(str_replace(['-', ' ', '.'], '_', $key));
    }

    private function neutralizeControls(string $value): string
    {
        return preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '[INVALID_TEXT]';
    }
}
