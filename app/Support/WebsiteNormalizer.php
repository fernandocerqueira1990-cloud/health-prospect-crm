<?php

namespace App\Support;

class WebsiteNormalizer
{
    public static function normalize(?string $website): ?string
    {
        $website = trim((string) $website);

        if ($website === '') {
            return null;
        }

        if (preg_match('~^[a-z][a-z0-9+.-]*://~i', $website) !== 1) {
            $website = 'https://'.$website;
        }

        $parts = parse_url($website);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return $website;
        }

        $normalized = strtolower($parts['scheme']).'://'.strtolower($parts['host']);
        $normalized .= isset($parts['port']) ? ':'.$parts['port'] : '';
        $normalized .= $parts['path'] ?? '';
        $normalized .= isset($parts['query']) ? '?'.$parts['query'] : '';
        $normalized .= isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $normalized;
    }
}
