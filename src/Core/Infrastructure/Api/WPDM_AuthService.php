<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Api;

use WPDM\Shared\Encryption\WPDM_Encryption;

class WPDM_AuthService
{
    private const TRANSIENT_TOKEN = 'wpdm_access_token';
    private const TRANSIENT_AUTH  = 'wpdm_authorization_token';
    private const MARGIN_SECONDS  = 300; // renovar 5 min antes de expirar

    /**
     * Obtiene un token válido. Si hay uno en cache y no ha expirado, lo reutiliza.
     * Si no, solicita uno nuevo al endpoint.
     *
     * @return array{success: bool, message: string, data?: array}
     */
    public static function getToken(bool $forceRefresh = false): array
    {
        if (!$forceRefresh) {
            $cached = get_transient(self::TRANSIENT_TOKEN);
            if ($cached !== false) {
                return [
                    'success' => true,
                    'message' => 'Token válido (cache).',
                    'data'    => $cached,
                ];
            }
        }

        return self::requestNewToken();
    }

    /**
     * Solicita un nuevo token al endpoint de autenticación.
     *
     * @return array{success: bool, message: string, data?: array}
     */
    private static function requestNewToken(): array
    {
        $endpoint = get_option('wpdm_api_endpoint', '');
        $user     = get_option('wpdm_api_user', '');
        $password = get_option('wpdm_api_password', '');

        if (empty($endpoint) || empty($user) || empty($password)) {
            return [
                'success' => false,
                'message' => 'Faltan credenciales. Guarda el endpoint, usuario y contraseña primero.',
            ];
        }

        $body = wp_json_encode([
            'NomUsuario'   => WPDM_Encryption::decrypt($user),
            'ClaveUsuario' => WPDM_Encryption::decrypt($password),
        ]);

        $response = wp_remote_post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => $body,
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'message' => 'Error de conexión: ' . $response->get_error_message(),
            ];
        }

        $code = wp_remote_retrieve_response_code($response);
        $responseBody = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($responseBody['access_token'])) {
            $error_msg = $responseBody['message'] ?? $responseBody['title'] ?? "Respuesta HTTP {$code}";
            return [
                'success' => false,
                'message' => 'Error de autenticación: ' . $error_msg,
            ];
        }

        $expiresIn = (int) ($responseBody['expires_in'] ?? 0);
        $ttl = max($expiresIn - self::MARGIN_SECONDS, 60);

        $tokenData = [
            'access_token'        => $responseBody['access_token'],
            'authorization_token' => $responseBody['authorization_token'] ?? '',
            'token_type'          => $responseBody['token_type'] ?? 'Bearer',
            'expires_in'          => $expiresIn,
            'usuario'             => $responseBody['data']['NomUsuario'] ?? '',
            'id_usuario'          => $responseBody['data']['IdUsuario'] ?? '',
        ];

        set_transient(self::TRANSIENT_TOKEN, $tokenData, $ttl);
        set_transient(self::TRANSIENT_AUTH, $responseBody['authorization_token'] ?? '', $ttl);

        return [
            'success' => true,
            'message' => 'Conexión exitosa. Token generado.',
            'data'    => $tokenData,
        ];
    }

    /**
     * Devuelve el authorization_token listo para usar en headers.
     * Retorna null si no hay token válido.
     */
    public static function getAuthorizationHeader(): ?string
    {
        $result = self::getToken();

        if (!$result['success']) {
            return null;
        }

        $type  = $result['data']['token_type'] ?? 'Bearer';
        $token = $result['data']['authorization_token'] ?? '';

        return "{$type} {$token}";
    }

    /**
     * Invalida el token almacenado en cache.
     */
    public static function invalidateToken(): void
    {
        delete_transient(self::TRANSIENT_TOKEN);
        delete_transient(self::TRANSIENT_AUTH);
    }
}
