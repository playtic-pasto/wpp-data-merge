<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Api;

use WPDM\Shared\Encryption\WPDM_Encryption;
use WPDM\Core\Domain\Auth\AuthResult;
use WPDM\Core\Domain\Auth\AuthServiceInterface;
use WPDM\Core\Domain\Auth\AuthToken;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Permite gestionar la autenticación con el endpoint de la API externa para el plugin WP Data Merge, incluyendo la obtención, almacenamiento en cache y renovación de tokens de acceso.
 * 
 * @name WPDM_AuthService
 * @package WPDM\Core\Infrastructure\Api
 * @since 1.0.0
 */
class WPDM_AuthService implements AuthServiceInterface
{
    private const TRANSIENT_TOKEN = 'wpdm_access_token';
    private const TRANSIENT_AUTH  = 'wpdm_authorization_token';
    private const MARGIN_SECONDS  = 300; // renovar 5 min antes de expirar

    /**
     * Obtiene un token de autenticación válido, ya sea desde cache o solicitando uno nuevo si es necesario o si se fuerza la renovación. 
     * El resultado incluye el token generado o el mensaje de error en caso de fallo.
     * 
      * @param bool $forceRefresh Indica si se debe forzar la renovación del token, ignorando el cache.
      * @return AuthResult El resultado de la operación de autenticación, incluyendo el token generado o el mensaje de error en caso de fallo.
      * @access public
     */
    public static function getToken(bool $forceRefresh = false): AuthResult
    {
        if (!$forceRefresh) {
            $cached = get_transient(self::TRANSIENT_TOKEN);
            if ($cached !== false) {
                return AuthResult::success('Token válido (cache).', AuthToken::fromArray($cached));
            }
        }

        return self::requestNewToken();
    }

    /**
     * Solicita un nuevo token de autenticación al endpoint utilizando las credenciales almacenadas. 
     * Si la solicitud es exitosa, el token se almacena en cache con un TTL basado en expires_in. 
     * En caso de error, se devuelve un resultado de autenticación con el mensaje de error correspondiente.
     * 
     * @return AuthResult El resultado de la operación de autenticación, incluyendo el token generado o el mensaje de error en caso de fallo.
     * @access private
     */
    private static function requestNewToken(): AuthResult
    {
        $logger      = new WPDM_Logger(WPDM_PATH);
        $credentials = self::loadCredentials();

        if ($credentials === null) {
            $message = 'Faltan credenciales. Guarda el endpoint, usuario y contraseña primero.';
            $logger->warning('Auth: ' . $message);
            return AuthResult::failure($message);
        }

        $logger->info("Auth: enviando solicitud a {$credentials['endpoint']}");

        $response = self::sendAuthRequest($credentials);

        if (is_wp_error($response)) {
            $message = 'Error de conexión: ' . $response->get_error_message();
            $logger->error('Auth: ' . $message);
            return AuthResult::failure($message);
        }

        $code         = wp_remote_retrieve_response_code($response);
        $responseBody = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200 || empty($responseBody['access_token'])) {
            $error   = $responseBody['message'] ?? $responseBody['title'] ?? "Respuesta HTTP {$code}";
            $message = 'Error de autenticación: ' . $error;
            $logger->error(sprintf(
                'Auth: %s | URL: %s | HTTP: %d | Body: %s',
                $message,
                $credentials['endpoint'],
                $code,
                wp_json_encode($responseBody)
            ));
            return AuthResult::failure($message);
        }

        $token = self::parseTokenData($responseBody);
        self::cacheToken($token, $responseBody);

        $logger->info("Auth: token generado correctamente para usuario {$token->usuario}");

        return AuthResult::success('Conexión exitosa. Token generado.', $token);
    }

    /**
     * Carga y descifra las credenciales desde wp_options.
     * Retorna null si alguna credencial está vacía.
     *
     * @return array{endpoint: string, user: string, password: string}|null
     */
    private static function loadCredentials(): ?array
    {
        $apiConfig = require WPDM_PATH . 'config/api.php';

        $endpoint = get_option($apiConfig['option_endpoint'], '');
        $user     = get_option($apiConfig['option_user'], '');
        $password = get_option($apiConfig['option_password'], '');

        if (empty($endpoint) || empty($user) || empty($password)) {
            return null;
        }

        return [
            'endpoint' => rtrim($endpoint, '/') . $apiConfig['auth_path'],
            'user'     => WPDM_Encryption::decrypt($user),
            'password' => WPDM_Encryption::decrypt($password),
            'timeout'  => $apiConfig['timeout'],
        ];
    }

    /**
     * Envía la petición HTTP de autenticación al endpoint.
     *
     * @param array{endpoint: string, user: string, password: string, timeout: int} $credentials
     * @return \WP_Error|array
     */
    private static function sendAuthRequest(array $credentials): mixed
    {
        $body = wp_json_encode([
            'NomUsuario'   => $credentials['user'],
            'ClaveUsuario' => $credentials['password'],
        ]);

        return wp_remote_post($credentials['endpoint'], [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => $body,
            'timeout' => $credentials['timeout'],
        ]);
    }

    /**
     * Extrae los datos del token desde la respuesta de la API.
     *
     * @param array<string, mixed> $responseBody
     */
    private static function parseTokenData(array $responseBody): AuthToken
    {
        return new AuthToken(
            accessToken:        $responseBody['access_token'],
            authorizationToken: $responseBody['authorization_token'] ?? '',
            tokenType:          $responseBody['token_type'] ?? 'Bearer',
            expiresIn:          (int) ($responseBody['expires_in'] ?? 0),
            usuario:            $responseBody['data']['NomUsuario'] ?? '',
            idUsuario:          (string) ($responseBody['data']['IdUsuario'] ?? ''),
        );
    }

    /**
     * Almacena el token en transients con TTL calculado desde expires_in.
     *
     * @param AuthToken $token El token de autenticación a almacenar en cache.
     * @param array<string, mixed> $responseBody La respuesta completa de la API, utilizada para almacenar también el authorization_token si está presente.
     */
    private static function cacheToken(AuthToken $token, array $responseBody): void
    {
        $ttl = max($token->expiresIn - self::MARGIN_SECONDS, 60);

        set_transient(self::TRANSIENT_TOKEN, $token->toArray(), $ttl);
        set_transient(self::TRANSIENT_AUTH, $responseBody['authorization_token'] ?? '', $ttl);
    }

    /**
     * Devuelve el access_token listo para usar en headers.
     * Retorna null si no hay token válido.
     * 
     * @return string|null El token de autorización formateado para el header, o null si no se pudo obtener un token válido.
     * @access public
     */
    public static function getAuthorizationHeader(): ?string
    {
        $result = self::getToken();

        if (!$result->success) {
            return null;
        }

        return $result->token?->authorizationHeader();
    }

    /**
     * Elimina los tokens almacenados en cache, forzando que la próxima solicitud de token genere uno nuevo. Esto es útil para casos donde se sospecha que el token ha sido comprometido o simplemente se desea forzar una renovación manual.
     * @return void
     * @access public
     */
    public static function invalidateToken(): void
    {
        delete_transient(self::TRANSIENT_TOKEN);
        delete_transient(self::TRANSIENT_AUTH);
    }
}
