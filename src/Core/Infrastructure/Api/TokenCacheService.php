<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Api;

use WPDM\Core\Domain\Auth\AuthToken;

/**
 * Servicio responsable de gestionar el cache de tokens de autenticación usando transients de WordPress.
 * 
 * @name TokenCacheService
 * @package WPDM\Core\Infrastructure\Api
 * @since 1.0.0
 */
class TokenCacheService
{
    private const TRANSIENT_TOKEN = 'wpdm_access_token';
    private const TRANSIENT_AUTH  = 'wpdm_authorization_token';
    private const MARGIN_SECONDS  = 300; // renovar 5 min antes de expirar

    /**
     * Obtiene el token almacenado en cache si existe y aún es válido.
     * 
     * @return AuthToken|null
     */
    public function getCachedToken(): ?AuthToken
    {
        $cached = get_transient(self::TRANSIENT_TOKEN);
        
        if ($cached === false) {
            return null;
        }

        return AuthToken::fromArray($cached);
    }

    /**
     * Almacena un token en cache con TTL basado en su tiempo de expiración.
     * 
     * @param AuthToken $token
     * @param string|null $authorizationToken Token de autorización adicional (opcional).
     * @return void
     */
    public function cacheToken(AuthToken $token, ?string $authorizationToken = null): void
    {
        $ttl = max($token->expiresIn - self::MARGIN_SECONDS, 60);

        set_transient(self::TRANSIENT_TOKEN, $token->toArray(), $ttl);
        
        if ($authorizationToken !== null) {
            set_transient(self::TRANSIENT_AUTH, $authorizationToken, $ttl);
        }
    }

    /**
     * Invalida el token almacenado en cache, forzando que la próxima solicitud genere uno nuevo.
     * 
     * @return void
     */
    public function invalidateToken(): void
    {
        delete_transient(self::TRANSIENT_TOKEN);
        delete_transient(self::TRANSIENT_AUTH);
    }
}
