<?php

declare(strict_types=1);

namespace WPDM\Core\Domain\Auth;

/**
 * Puerto (Gateway) que define el contrato para el servicio de autenticación.
 * La infraestructura implementa esta interfaz; el dominio/aplicación solo depende de ella.
 *
 * @name AuthServiceInterface
 * @package WPDM\Core\Domain\Auth
 * @since 1.0.0
 */
interface AuthServiceInterface
{
    /**
     * Obtiene un token válido, usando caché si está disponible.
     *
     * @param bool $forceRefresh Fuerza una nueva petición ignorando el caché.
     */
    public static function getToken(bool $forceRefresh = false): AuthResult;

    /**
     * Devuelve el header de autorización listo para usar en requests HTTP.
     * Retorna null si no hay token válido.
     */
    public static function getAuthorizationHeader(): ?string;

    /**
     * Invalida el token almacenado en caché.
     */
    public static function invalidateToken(): void;
}
