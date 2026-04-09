<?php

declare(strict_types=1);

namespace WPDM\Shared\Encryption;

/**
 * Permite cifrar y descifrar datos sensibles utilizando el método de cifrado AES-256-CBC, proporcionando una capa adicional de seguridad para la información almacenada en la base de datos de WordPress, como las credenciales de la API.
 * 
 * @name WPDM_Encryption
 * @package WPDM\Shared\Encryption
 * @since 1.0.0
 */
class WPDM_Encryption
{
    private const METHOD = 'aes-256-cbc';

    /**
     * Genera una clave de cifrado única utilizando las constantes de seguridad de WordPress (AUTH_KEY y SECURE_AUTH_KEY), aplicando un hash SHA-256 para obtener una cadena de 32 caracteres que se utilizará como clave para el cifrado y descifrado de datos sensibles en el plugin WP Data Merge.
     * 
     * @return string La clave de cifrado generada.
     */
    private static function getKey(): string
    {
        return substr(hash('sha256', AUTH_KEY . SECURE_AUTH_KEY), 0, 32);
    }

    /**
     * Cifra un valor utilizando el método de cifrado AES-256-CBC, generando un vector de inicialización (IV) aleatorio y combinándolo con el valor cifrado para crear una cadena codificada en base64 que se puede almacenar de manera segura en la base de datos de WordPress.
     * 
     * @param string $value El valor a cifrar.
     * @return string El valor cifrado codificado en base64.
     */
    public static function encrypt(string $value): string
    {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(self::METHOD));
        $encrypted = openssl_encrypt($value, self::METHOD, self::getKey(), 0, $iv);

        return base64_encode($iv . '::' . $encrypted);
    }

    /**
     * Descifra un valor previamente cifrado utilizando el método de cifrado AES-256-CBC, extrayendo el vector de inicialización (IV) y el valor cifrado de la cadena codificada en base64.
     * 
     * @param string $value El valor cifrado codificado en base64.
     * @return string El valor descifrado.
     */
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
