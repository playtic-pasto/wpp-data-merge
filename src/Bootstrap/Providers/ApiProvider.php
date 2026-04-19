<?php

declare(strict_types=1);

namespace WPDM\Bootstrap\Providers;

use WPDM\Bootstrap\ServiceProvider;
use WPDM\Core\Infrastructure\Api\WPDM_AuthService;
use WPDM\Core\Infrastructure\WordPress\Api\Api;
use WPDM\Core\Infrastructure\WordPress\Controllers\ConnectionTestController;
use WPDM\Core\Infrastructure\WordPress\Controllers\SettingsController;
use WPDM\Shared\Container\Container;
use WPDM\Shared\Encryption\WPDM_Encryption;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Controllers y hooks del panel de Conexión API (guardado + prueba).
 *
 * @name ApiProvider
 * @package WPDM\Bootstrap\Providers
 * @since 1.0.0
 */
class ApiProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(SettingsController::class, fn(Container $container) => new SettingsController(
            $container->get(WPDM_Encryption::class),
            $container->get(WPDM_Logger::class),
            $container->get(WPDM_AuthService::class)
        ));

        $container->bind(ConnectionTestController::class, fn(Container $container) => new ConnectionTestController(
            $container->get(WPDM_AuthService::class)
        ));

        $container->bind(Api::class, fn(Container $container) => new Api(
            $container->get(SettingsController::class),
            $container->get(ConnectionTestController::class)
        ));
    }

    public function boot(Container $container): void
    {
        $container->get(Api::class)->register();
    }
}
