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
    public function register(Container $c): void
    {
        $c->bind(SettingsController::class, fn(Container $c) => new SettingsController(
            $c->get(WPDM_Encryption::class),
            $c->get(WPDM_Logger::class),
            $c->get(WPDM_AuthService::class)
        ));

        $c->bind(ConnectionTestController::class, fn(Container $c) => new ConnectionTestController(
            $c->get(WPDM_AuthService::class)
        ));

        $c->bind(Api::class, fn(Container $c) => new Api(
            $c->get(SettingsController::class),
            $c->get(ConnectionTestController::class)
        ));
    }

    public function boot(Container $c): void
    {
        $c->get(Api::class)->register();
    }
}
