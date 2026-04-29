<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Api;

use WPDM\Shared\Encryption\WPDM_Encryption;

/**
 * Carga y descifra las credenciales de autenticación desde wp_options,
 * normalizando la URL base para evitar duplicación de rutas.
 *
 * @name CredentialLoader
 * @package WPDM\Core\Infrastructure\Api
 * @since 1.0.0
 */
class CredentialLoader
{
    /** @var array<string, mixed> */
    private array $apiConfig;

    private WPDM_Encryption $encryption;

    public function __construct(?WPDM_Encryption $encryption = null)
    {
        $this->apiConfig  = require WPDM_PATH . 'config/api.php';
        $this->encryption = $encryption ?? new WPDM_Encryption();
    }

    /**
     * @return array{endpoint: string, base_url: string, user: string, password: string, timeout: int}|null
     */
    public function loadCredentials(): ?array
    {
        $endpoint = (string) get_option($this->apiConfig['option_endpoint'], '');
        $user     = (string) get_option($this->apiConfig['option_user'], '');
        $password = (string) get_option($this->apiConfig['option_password'], '');

        if ($endpoint === '' || $user === '' || $password === '') {
            return null;
        }

        $baseUrl = $this->normalizeBaseUrl($endpoint);

        return [
            'endpoint' => $baseUrl . $this->apiConfig['auth_path'],
            'base_url' => $baseUrl,
            'user'     => $this->encryption->decryptValue($user),
            'password' => $this->encryption->decryptValue($password),
            'timeout'  => (int) $this->apiConfig['timeout'],
        ];
    }

    /**
     * Devuelve únicamente la URL base (sin rutas conocidas) aunque el usuario
     * haya guardado la opción con el auth_path o api_base incluidos.
     */
    public function getBaseUrl(): string
    {
        $endpoint = (string) get_option($this->apiConfig['option_endpoint'], '');
        return $endpoint === '' ? '' : $this->normalizeBaseUrl($endpoint);
    }

    private function normalizeBaseUrl(string $endpoint): string
    {
        $endpoint = rtrim(trim($endpoint), '/');

        $suffixes = [
            $this->apiConfig['auth_path'] ?? '',
            $this->apiConfig['api_base'] ?? '',
        ];

        foreach ($suffixes as $suffix) {
            $suffix = (string) $suffix;
            if ($suffix === '') {
                continue;
            }
            $len = strlen($suffix);
            if (strcasecmp(substr($endpoint, -$len), $suffix) === 0) {
                $endpoint = rtrim(substr($endpoint, 0, -$len), '/');
            }
        }

        return $endpoint;
    }
}
