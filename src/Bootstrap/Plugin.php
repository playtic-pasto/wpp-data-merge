<?php

declare(strict_types=1);

namespace WPDM\Bootstrap;

use WPDM\Shared\Container\Container;
use WPDM\Shared\Logger\WPDM_Logger;
use WPDM\Core\Infrastructure\Database\WPDM_Database;
use WPDM\Core\Infrastructure\WordPress\Admin\Menu;
use WPDM\Core\Infrastructure\WordPress\Hooks\Actions;
use WPDM\Core\Infrastructure\WordPress\Hooks\Resources;
use WPDM\Core\Infrastructure\WordPress\Cron\CronScheduler;
use WPDM\Core\Infrastructure\WordPress\Api\Api;

/**
 * Permite inicializar el plugin WP Data Merge, registrando los hooks necesarios para su funcionamiento en WordPress.
 * 
 * @name Plugin
 * @package WPDM\Bootstrap
 * @since 1.0.0
 */
final class Plugin
{
    /**
     * Define el contenedor de dependencias para el plugin, permitiendo gestionar las instancias de las clases utilizadas en el plugin de manera centralizada.
     * @var Container
     * @access private
     */
    private static Container $container;

    public static function init(): void
    {
        self::registerHooks();
    }

    /**
     * Registra los hooks de activación, desactivación y carga de plugins para el plugin WP Data Merge.
     * @return void
     * @access private
     */
    private static function registerHooks(): void
    {
        register_activation_hook(WPDM_FILE, [self::class, 'activate']);
        register_deactivation_hook(WPDM_FILE, [self::class, 'deactivate']);
        add_action('plugins_loaded', [self::class, 'boot']);
    }

    /**
     * Inicializa el plugin, registrando los hooks relacionados con el menú de administración, las acciones personalizadas, la carga de recursos y la programación de tareas cron.
     * @return void
     * @access public
     */
    public static function boot(): void
    {
        self::$container = new Container();

        self::registerBindings();
        (new Menu())->register();
        (new Actions())->register();
        (new Resources())->register();
        (new CronScheduler())->register();
        (new Api())->register();
    }

    /**
     * Registra las dependencias necesarias para el funcionamiento del plugin en el contenedor de dependencias, permitiendo su gestión centralizada y facilitando la inyección de dependencias en las clases que lo requieran.
     * 
     * @return void
     * @access private
     */
    private static function registerBindings(): void
    {
        self::$container->bind(WPDM_Logger::class, function () {
            return new WPDM_Logger(WPDM_PATH);
        });
    }

    /**
     * Obtiene la etiqueta del usuario actual, incluyendo su nombre de usuario y ID.
     * @return string
     * @access private
     */
    private static function getCurrentUserLabel(): string
    {
        $user = wp_get_current_user();
        return $user->exists()
            ? "{$user->user_login} (ID: {$user->ID})"
            : 'usuario desconocido';
    }

    /**
     * Maneja la activación del plugin, ejecutando las migraciones de la base de datos necesarias y registrando un mensaje en el log indicando que el plugin ha sido activado, junto con la etiqueta del usuario que realizó la acción.
     * @return void
     * @access public
     */
    public static function activate(): void
    {
        WPDM_Database::migrate();
        $logger = new WPDM_Logger(WPDM_PATH);
        $logger->info('Plugin activado por: ' . self::getCurrentUserLabel());
    }

    /**
     * Maneja la desactivación del plugin, registrando un mensaje en el log indicando que el plugin ha sido desactivado, junto con la etiqueta del usuario que realizó la acción.
     * @return void
     * @access public
     */
    public static function deactivate(): void
    {
        $logger = new WPDM_Logger(WPDM_PATH);
        $logger->info('Plugin desactivado por: ' . self::getCurrentUserLabel());
    }
}
