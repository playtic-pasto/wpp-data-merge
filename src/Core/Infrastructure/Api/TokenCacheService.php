<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Api;

use WPDM\Core\Domain\Auth\AuthToken;

/**
 * Servicio responsable de persistir el token de autenticación en wp_options, permitiendo
 * reutilizarlo entre peticiones hasta que expire.
 *
 * @name TokenCacheService
 * @package WPDM\Core\Infrastructure\Api
 * @since 1.0.0
 */
class TokenCacheService
{
    private const OPTION_KEY     = 'wpdm_auth_token';
    private const MARGIN_SECONDS = 300;

    /**
     * Devuelve el token persistido si todavía es válido.
     */
    public function getCachedToken(): ?AuthToken
    {
        $stored = get_option(self::OPTION_KEY, null);

        if (!is_array($stored) || empty($stored['access_token'])) {
            return null;
        }

        $expiresAt = (int) ($stored['expires_at'] ?? 0);
        if ($expiresAt > 0 && $expiresAt <= (time() + self::MARGIN_SECONDS)) {
            return null;
        }

        return AuthToken::fromArray($stored);
    }

    /**
     * Persiste el token en wp_options con un timestamp absoluto de expiración.
     *
     * @param AuthToken $token
     * @param string|null $authorizationToken Token adicional que devuelve SINCO (opcional).
     */
    public function cacheToken(AuthToken $token, ?string $authorizationToken = null): void
    {
        $payload = $token->toArray();

        if ($authorizationToken !== null && $authorizationToken !== '') {
            $payload['authorization_token'] = $authorizationToken;
        }

        $payload['expires_at'] = time() + max((int) $token->expiresIn, 0);
        $payload['cached_at']  = time();

        update_option(self::OPTION_KEY, $payload, false);
    }

    /**
     * Elimina el token persistido, forzando la obtención de uno nuevo.
     */
    public function invalidateToken(): void
    {
        delete_option(self::OPTION_KEY);
    }
}
