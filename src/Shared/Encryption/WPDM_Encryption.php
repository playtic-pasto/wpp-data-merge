<?php

declare(strict_types=1);

namespace WPDM\Shared\Encryption;

class WPDM_Encryption
{
    private const METHOD = 'aes-256-cbc';

    private static function getKey(): string
    {
        return substr(hash('sha256', AUTH_KEY . SECURE_AUTH_KEY), 0, 32);
    }

    public static function encrypt(string $value): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::METHOD));
        $encrypted = openssl_encrypt($value, self::METHOD, self::getKey(), 0, $iv);

        return base64_encode($iv . '::' . $encrypted);
    }

    public static function decrypt(string $value): string
    {
        $data = base64_decode($value);
        $parts = explode('::', $data, 2);

        if (count($parts) !== 2) {
            return '';
        }

        [$iv, $encrypted] = $parts;

        $decrypted = openssl_decrypt($encrypted, self::METHOD, self::getKey(), 0, $iv);

        return $decrypted !== false ? $decrypted : '';
    }
}
