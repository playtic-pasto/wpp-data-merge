<?php

namespace WPDM\Bootstrap;

use WPDM\Shared\Container\Container;
use WPDM\Shared\Logger\WPDM_Logger;
use WPDM\Core\Infrastructure\WordPress\Admin\Menu;
use WPDM\Core\Infrastructure\WordPress\Hooks\Actions;
use WPDM\Core\Infrastructure\Database\WPDM_Database;

final class Plugin
{

    private static Container $container;

    public static function init(): void
    {
        self::registerHooks();
    }

    private static function registerHooks(): void
    {
        register_activation_hook(WPDM_FILE, [self::class, 'activate']);
        register_deactivation_hook(WPDM_FILE, [self::class, 'deactivate']);
        add_action('plugins_loaded', [self::class, 'boot']);
    }

    public static function boot(): void
    {
        self::$container = new Container();

        self::registerBindings();
        (new Menu())->register();
        (new Actions())->register();
    }

    private static function registerBindings(): void
    {
        self::$container->bind(WPDM_Logger::class, function () {
            return new WPDM_Logger(WPDM_PATH);
        });
    }

    private static function getCurrentUserLabel(): string
    {
        $user = wp_get_current_user();
        return $user->exists()
            ? "{$user->user_login} (ID: {$user->ID})"
            : 'usuario desconocido';
    }

    public static function activate(): void
    {
        WPDM_Database::migrate();
        $logger = new WPDM_Logger(WPDM_PATH);
        $logger->info('Plugin activado por: ' . self::getCurrentUserLabel());
    }

    public static function deactivate(): void
    {
        $logger = new WPDM_Logger(WPDM_PATH);
        $logger->info('Plugin desactivado por: ' . self::getCurrentUserLabel());
    }
}
