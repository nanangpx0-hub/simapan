<?php

declare(strict_types=1);

namespace App\Support;

final class AuditSanitizer
{
    public const REDACTED = '[REDACTED]';

    /**
     * @var list<string>
     */
    private const SENSITIVE_KEYS = [
        'password',
        'password_confirmation',
        'current_password',
        'remember_token',
        'session',
        'session_id',
        'session_identifier',
        'access_token',
        'refresh_token',
        'api_token',
        'api_key',
        'secret',
        'encryption_key',
        'database_url',
        'db_password',
        'nik',
        'phone',
        'contact_person',
        'contact_phone',
        'telephone',
        'whatsapp',
        'email',
        'address',
        'alamat',
        'full_address',
        'document_content',
        'file_content',
        'uploaded_file',
        'attachment',
        'private_path',
    ];

    public static function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            $clean = [];

            foreach ($data as $key => $value) {
                if (is_string($key) && self::isSensitiveKey($key)) {
                    $clean[$key] = self::REDACTED;

                    continue;
                }

                $clean[$key] = self::sanitize($value);
            }

            return $clean;
        }

        return $data;
    }

    public static function isSensitiveKey(string $key): bool
    {
        return in_array(mb_strtolower($key), self::SENSITIVE_KEYS, true);
    }
}
