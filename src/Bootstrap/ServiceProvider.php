<?php

declare(strict_types=1);

namespace WPDM\Bootstrap;

use WPDM\Shared\Container\Container;

/**
 * Contrato para módulos que declaran dependencias y registran sus hooks de
 * WordPress. Cada provider agrupa un área funcional (Auth, Admin, Cron, etc.).
 *
 * @name ServiceProvider
 * @package WPDM\Bootstrap
 * @since 1.0.0
 */
interface ServiceProvider
{
    /**
     * Declara los bindings del provider en el contenedor.
     */
    public function register(Container $container): void;

    /**
     * Resuelve los servicios que deben enlazarse con hooks de WordPress.
     * Se invoca después de que todos los providers hayan registrado.
     */
    public function boot(Container $container): void;
}
