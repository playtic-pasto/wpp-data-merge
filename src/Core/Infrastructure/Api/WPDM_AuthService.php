<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Api;

use WPDM\Core\Domain\Auth\AuthResult;
use WPDM\Core\Domain\Auth\AuthServiceInterface;
use WPDM\Core\Domain\Auth\AuthToken;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Servicio de autenticación que orquesta la obtención, cache y validación de tokens.
 * Implementa SRP delegando responsabilidades a servicios especializados.
 * 
 * @name WPDM_AuthService
 * @package WPDM\Core\Infrastructure\Api
 * @since 1.0.0
 */
class WPDM_AuthService implements AuthServiceInterface
{
    private CredentialLoader $credentialLoader;
    private TokenCacheService $tokenCache;
    private HttpApiClient $httpClient;
    private WPDM_Logger $logger;

    public function __construct(
        ?CredentialLoader $credentialLoader = null,
        ?TokenCacheService $tokenCache = null,
        ?HttpApiClient $httpClient = null,
        ?WPDM_Logger $logger = null
    ) {
        $this->credentialLoader = $credentialLoader ?? new CredentialLoader();
        $this->tokenCache       = $tokenCache ?? new TokenCacheService();
        $this->httpClient       = $httpClient ?? new HttpApiClient();
        $this->logger           = $logger ?? new WPDM_Logger(WPDM_PATH);
    }

    /**
     * Obtiene un token de autenticación válido, ya sea desde cache o solicitando uno nuevo.
     * 
     * @param bool $forceRefresh Indica si se debe forzar la renovación del token, ignorando el cache.
     * @return AuthResult El resultado de la operación de autenticación.
     */
    public function getToken(bool $forceRefresh = false): AuthResult
    {
        if (!$forceRefresh) {
            $cachedToken = $this->tokenCache->getCachedToken();
            if ($cachedToken !== null) {
                return AuthResult::success('Token válido (cache).', $cachedToken);
            }
        }

        return $this->requestNewToken();
    }

    /**
     * Solicita un nuevo token de autenticación al endpoint.
     * 
     * @return AuthResult
     */
    private function requestNewToken(): AuthResult
    {
        $credentials = $this->credentialLoader->loadCredentials();

        if ($credentials === null) {
            $message = 'Faltan credenciales. Guarda el endpoint, usuario y contraseña primero.';
            $this->logger->warning('Auth: ' . $message);
            return AuthResult::failure($message);
        }

        $this->logger->info("Auth: enviando solicitud a {$credentials['endpoint']}");

        $response = $this->httpClient->post(
            $credentials['endpoint'],
            [
                'NomUsuario'   => $credentials['user'],
                'ClaveUsuario' => $credentials['password'],
            ],
            [],
            $credentials['timeout']
        );

        if (is_wp_error($response)) {
            $message = 'Error de conexión: ' . $response->get_error_message();
            $this->logger->error('Auth: ' . $message);
            return AuthResult::failure($message);
        }

        $code = $this->httpClient->getResponseCode($response);
        $responseBody = $this->httpClient->getResponseBody($response);

        if ($code !== 200 || empty($responseBody['access_token'])) {
            $error   = $responseBody['message'] ?? $responseBody['title'] ?? "Respuesta HTTP {$code}";
            $message = 'Error de autenticación: ' . $error;
            $this->logger->error(sprintf(
                'Auth: %s | URL: %s | HTTP: %d | Body: %s',
                $message,
                $credentials['endpoint'],
                $code,
                wp_json_encode($responseBody)
            ));
            return AuthResult::failure($message);
        }

        $token = $this->parseTokenData($responseBody);
        $this->tokenCache->cacheToken($token, $responseBody['authorization_token'] ?? null);

        $this->logger->info("Auth: token generado correctamente para usuario {$token->usuario}");

        return AuthResult::success('Conexión exitosa. Token generado.', $token);
    }

    /**
     * Extrae los datos del token desde la respuesta de la API.
     *
     * @param array<string, mixed> $responseBody
     * @return AuthToken
     */
    private function parseTokenData(array $responseBody): AuthToken
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
     * Devuelve el header de autorización listo para usar en requests HTTP.
     * 
     * @return string|null
     */
    public function getAuthorizationHeader(): ?string
    {
        $result = $this->getToken();

        if (!$result->success) {
            return null;
        }

        return $result->token?->authorizationHeader();
    }

    /**
     * Invalida el token almacenado en cache.
     * 
     * @return void
     */
    public function invalidateToken(): void
    {
        $this->tokenCache->invalidateToken();
    }
}
