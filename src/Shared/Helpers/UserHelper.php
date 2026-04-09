<?php

declare(strict_types=1);

namespace WPDM\Shared\Helpers;

/**
 * Proporciona utilidades relacionadas con la información del usuario actual de WordPress.
 * 
 * @name UserHelper
 * @package WPDM\Shared\Helpers
 * @since 1.0.0
 */
class UserHelper
{
    /**
     * Obtiene una etiqueta descriptiva del usuario actual, incluyendo su nombre de usuario y su ID.
     * 
     * @return string La etiqueta del usuario en formato "username (ID: 123)" o "usuario desconocido" si no existe.
     */
    public static function getCurrentUserLabel(): string
    {
        $user = wp_get_current_user();
        return $user->exists()
            ? "{$user->user_login} (ID: {$user->ID})"
            : 'usuario desconocido';
    }
}
