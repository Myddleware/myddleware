<?php

namespace App\Service;

/**
 * Masks credentials (tokens, passwords, API keys, session ids, auth headers...)
 * in values that are about to be written to the logs.
 */
final class LogSanitizer
{
    public const MASK = '***';

    // Array keys whose value must never be logged
    private const SENSITIVE_KEY = '/(token|passw|pwd|secret|api_?key|accesskey|authorization|oauth|cookie|session_?id|sessionname|private_?key|signature|credential)/i';

    // Pattern => replacement applied on every logged string
    private const STRING_PATTERNS = [
        // Query string / form parameters: ?wstoken=xxx&api_key=yyy
        '/([?&](?:[a-z_]*token|api_?key|apikey|key|password|pwd|passwd|secret|client_secret|signature|session_?id|sessionname)=)[^&\s"\'<>]+/i' => '$1'.self::MASK,
        // JSON: "access_token":"xxx"
        '/("(?:[a-z_]*token|password|passwd|pwd|[a-z_]*secret|api_?key|apikey|accesskey|session_?id|sessionname|authorization)"\s*:\s*)"(?:[^"\\\\]|\\\\.)*"/i' => '$1"'.self::MASK.'"',
        // HTTP headers: Authorization: Bearer xxx / oauth-token: xxx / X-API-Key: xxx
        '/\b((?:authorization|proxy-authorization|oauth-token|x-api-key|api-key|x-auth-token|cookie|set-cookie)\s*:\s*)[^\r\n"]+/i' => '$1'.self::MASK,
        // Bare schemes: Bearer xxx / Basic xxx
        '/\b(Bearer|Basic)\s+[A-Za-z0-9\-._~+\/=]+/' => '$1 '.self::MASK,
        // Credentials inside URLs: https://user:password@host
        '#(\b[a-z][a-z0-9+.\-]*://[^:/\s@]+:)[^@\s/]+@#i' => '$1'.self::MASK.'@',
    ];

    public static function sanitize(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 10) {
            return $value;
        }
        if (is_string($value)) {
            return self::sanitizeString($value);
        }
        if (is_array($value)) {
            $result = [];
            foreach ($value as $key => $item) {
                if (is_string($key) && self::isSensitiveKey($key) && (is_scalar($item) || null === $item)) {
                    $result[$key] = (null === $item || '' === $item) ? $item : self::MASK;
                } else {
                    $result[$key] = self::sanitize($item, $depth + 1);
                }
            }

            return $result;
        }

        return $value;
    }

    public static function sanitizeString(string $value): string
    {
        if ('' === $value) {
            return $value;
        }

        return preg_replace(array_keys(self::STRING_PATTERNS), array_values(self::STRING_PATTERNS), $value) ?? $value;
    }

    public static function isSensitiveKey(string $key): bool
    {
        return 1 === preg_match(self::SENSITIVE_KEY, $key);
    }
}
