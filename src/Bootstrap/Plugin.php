<?php

declare(strict_types=1);

namespace WPDM\Bootstrap;

use WPDM\Bootstrap\Providers\AdminProvider;
use WPDM\Bootstrap\Providers\ApiProvider;
use WPDM\Bootstrap\Providers\AuthProvider;
use WPDM\Bootstrap\Providers\CronProvider;
use WPDM\Bootstrap\Providers\SharedProvider;
use WPDM\Bootstrap\Providers\SincoProvider;
use WPDM\Shared\Container\Container;

/**
 * Punto de entrada del plugin. Orquesta la inicialización iterando sobre un
 * conjunto de ServiceProviders: primero cada uno declara sus bindings
 * (register), luego cada uno engancha sus hooks (boot).
 *
 * @name Plugin
 * @package WPDM\Bootstrap
 * @since 1.0.0
 */
final class Plugin
{
    private static Container $container;

    public static function init(): void
    {
        register_activation_hook(WPDM_FILE, [Lifecycle::class, 'activate']);
        register_deactivation_hook(WPDM_FILE, [Lifecycle::class, 'deactivate']);
        add_action('plugins_loaded', [self::class, 'boot']);
    }

    public static function boot(): void
    {
        self::$container = new Container();
        $providers = self::providers();

        foreach ($providers as $provider) {
            $provider->register(self::$container);
        }

        foreach ($providers as $provider) {
            $provider->boot(self::$container);
        }
    }

    /**
     * Lista ordenada de providers. El orden importa para resolver dependencias
     * (Shared/Auth antes que los que las consumen).
     *
     * @return list<ServiceProvider>
     */
    private static function providers(): array
    {
        return [
            new SharedProvider(),
            new AuthProvider(),
            new ApiProvider(),
            new SincoProvider(),
            new AdminProvider(),
            new CronProvider(),
        ];
    }

    public static function getContainer(): Container
    {
        return self::$container;
    }
}
