<?php

declare(strict_types=1);

namespace WPDM\Bootstrap;

use WPDM\Shared\Container\Container;
use WPDM\Shared\Logger\WPDM_Logger;
use WPDM\Shared\Helpers\UserHelper;
use WPDM\Shared\Encryption\WPDM_Encryption;
use WPDM\Core\Infrastructure\Database\WPDM_Database;
use WPDM\Core\Infrastructure\Api\WPDM_AuthService;
use WPDM\Core\Infrastructure\Api\CredentialLoader;
use WPDM\Core\Infrastructure\Api\TokenCacheService;
use WPDM\Core\Infrastructure\Api\HttpApiClient;
use WPDM\Core\Infrastructure\WordPress\Admin\Menu;
use WPDM\Core\Infrastructure\WordPress\Hooks\Actions;
use WPDM\Core\Infrastructure\WordPress\Hooks\Resources;
use WPDM\Core\Infrastructure\WordPress\Cron\CronScheduler;
use WPDM\Core\Infrastructure\WordPress\Api\Api;
use WPDM\Core\Infrastructure\WordPress\Controllers\SettingsController;
use WPDM\Core\Infrastructure\WordPress\Controllers\ConnectionTestController;
use WPDM\Core\Infrastructure\Api\SincoApiClient;
use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Infrastructure\WordPress\Admin\Pages\ProjectsPage;

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
     * Inicializa el plugin, resolviendo dependencias desde el container y registrando los hooks.
     * @return void
     * @access public
     */
    public static function boot(): void
    {
        self::$container = new Container();

        self::registerBindings();
        
        // Resolver servicios desde el container
        self::$container->get(Menu::class)->register();
        self::$container->get(Actions::class)->register();
        self::$container->get(Resources::class)->register();
        self::$container->get(CronScheduler::class)->register();
        self::$container->get(Api::class)->register();
    }

    /**
     * Registra las dependencias necesarias en el contenedor con inyección de dependencias completa.
     * 
     * @return void
     * @access private
     */
    private static function registerBindings(): void
    {
        // Shared Services
        self::$container->bind(WPDM_Logger::class, function () {
            return new WPDM_Logger(WPDM_PATH);
        });

        self::$container->bind(WPDM_Encryption::class, function () {
            return new WPDM_Encryption();
        });

        // Auth Infrastructure
        self::$container->bind(CredentialLoader::class, function (Container $c) {
            return new CredentialLoader($c->get(WPDM_Encryption::class));
        });

        self::$container->bind(TokenCacheService::class, function () {
            return new TokenCacheService();
        });

        self::$container->bind(HttpApiClient::class, function () {
            return new HttpApiClient();
        });

        self::$container->bind(WPDM_AuthService::class, function (Container $c) {
            return new WPDM_AuthService(
                $c->get(CredentialLoader::class),
                $c->get(TokenCacheService::class),
                $c->get(HttpApiClient::class),
                $c->get(WPDM_Logger::class)
            );
        });

        // Controllers
        self::$container->bind(SettingsController::class, function (Container $c) {
            return new SettingsController(
                $c->get(WPDM_Encryption::class),
                $c->get(WPDM_Logger::class),
                $c->get(WPDM_AuthService::class)
            );
        });

        self::$container->bind(ConnectionTestController::class, function (Container $c) {
            return new ConnectionTestController($c->get(WPDM_AuthService::class));
        });

        // WordPress Infrastructure
        self::$container->bind(Api::class, function (Container $c) {
            return new Api(
                $c->get(SettingsController::class),
                $c->get(ConnectionTestController::class)
            );
        });

        // Projects
        self::$container->bind(SincoApiClient::class, function (Container $c) {
            return new SincoApiClient(
                $c->get(HttpApiClient::class),
                $c->get(WPDM_AuthService::class),
                $c->get(WPDM_Logger::class),
                $c->get(CredentialLoader::class)
            );
        });

        self::$container->bind(ProjectsRepository::class, function () {
            return new ProjectsRepository();
        });

        self::$container->bind(ProjectsPage::class, function (Container $c) {
            return new ProjectsPage(
                $c->get(ProjectsRepository::class),
                $c->get(SincoApiClient::class)
            );
        });

        self::$container->bind(Menu::class, function (Container $c) {
            return new Menu(
                null,
                null,
                null,
                $c->get(ProjectsPage::class),
                null
            );
        });

        self::$container->bind(Actions::class, function () {
            return new Actions();
        });

        self::$container->bind(Resources::class, function () {
            return new Resources();
        });

        self::$container->bind(CronScheduler::class, function () {
            return new CronScheduler();
        });
    }

    /**
     * Obtiene el contenedor de dependencias (útil para testing).
     * 
     * @return Container
     */
    public static function getContainer(): Container
    {
        return self::$container;
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
        $logger->info('Plugin activado por: ' . UserHelper::getCurrentUserLabel());
    }

    /**
     * Maneja la desactivación del plugin, registrando un mensaje en el log indicando que el plugin ha sido desactivado, junto con la etiqueta del usuario que realizó la acción.
     * @return void
     * @access public
     */
    public static function deactivate(): void
    {
        $logger = new WPDM_Logger(WPDM_PATH);
        $logger->info('Plugin desactivado por: ' . UserHelper::getCurrentUserLabel());
    }
}
