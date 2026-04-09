<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Api;

use WPDM\Shared\Encryption\WPDM_Encryption;

/**
 * Servicio responsable de cargar y descifrar las credenciales de autenticación desde las opciones de WordPress.
 * 
 * @name CredentialLoader
 * @package WPDM\Core\Infrastructure\Api
 * @since 1.0.0
 */
class CredentialLoader
{
    /** @var array<string, string> */
    private array $apiConfig;

    private WPDM_Encryption $encryption;

    public function __construct(?WPDM_Encryption $encryption = null)
    {
        $this->apiConfig = require WPDM_PATH . 'config/api.php';
        $this->encryption = $encryption ?? new WPDM_Encryption();
    }

    /**
     * Carga y descifra las credenciales desde wp_options.
     * Retorna null si alguna credencial está vacía.
     *
     * @return array{endpoint: string, user: string, password: string, timeout: int}|null
     */
    public function loadCredentials(): ?array
    {
        $endpoint = get_option($this->apiConfig['option_endpoint'], '');
        $user     = get_option($this->apiConfig['option_user'], '');
        $password = get_option($this->apiConfig['option_password'], '');

        if (empty($endpoint) || empty($user) || empty($password)) {
            return null;
        }

        return [
            'endpoint' => rtrim($endpoint, '/') . $this->apiConfig['auth_path'],
            'user'     => $this->encryption->decryptValue($user),
            'password' => $this->encryption->decryptValue($password),
            'timeout'  => $this->apiConfig['timeout'],
        ];
    }
}
