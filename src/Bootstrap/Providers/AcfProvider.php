<?php
//TODO: AQUI ES PARA REGISTRAR LOS ACF

declare(strict_types=1);

namespace WPDM\Bootstrap\Providers;

use WPDM\Bootstrap\ServiceProvider;
use WPDM\Core\Infrastructure\Api\SincoApiClient;
use WPDM\Core\Infrastructure\WordPress\Acf\ProjectFieldGroup;
use WPDM\Core\Infrastructure\WordPress\Acf\SyncCatalog;
use WPDM\Core\Infrastructure\WordPress\Acf\SyncDataFieldGroup;
use WPDM\Core\Infrastructure\WordPress\Acf\SyncFiltersFieldGroup;
use WPDM\Core\Infrastructure\WordPress\Acf\SyncFiltersReader;
use WPDM\Shared\Container\Container;

/**
 * Registra todos los grupos de campos ACF del plugin.
 *
 * Cada grupo se define en su propia clase dentro de Infrastructure\WordPress\Acf\
 * y se conecta al hook 'acf/init' desde este provider.
 *
 * Clases registradas:
 * @see ProjectFieldGroup      Campos de configuración (id_macroproject, ids_project).
 * @see SyncDataFieldGroup     Campos de datos sincronizados (precios, áreas, estados, tipos).
 * @see SyncFiltersFieldGroup  Options Page con filtros dinámicos (estados y tipos).
 * @see SyncCatalog            Catálogo dinámico de estados y tipos (wp_options).
 * @see SyncFiltersReader      Lee los filtros seleccionados para aplicarlos al sincronizar.
 */
class AcfProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(ProjectFieldGroup::class, fn(Container $container) => new ProjectFieldGroup(
            $container->get(SyncCatalog::class)
        ));
        $container->bind(SyncDataFieldGroup::class, fn() => new SyncDataFieldGroup());

        $container->bind(SyncCatalog::class, fn(Container $container) => new SyncCatalog(
            $container->get(SincoApiClient::class)
        ));

        $container->bind(SyncFiltersFieldGroup::class, fn(Container $container) => new SyncFiltersFieldGroup(
            $container->get(SyncCatalog::class)
        ));

        $container->bind(SyncFiltersReader::class, fn() => new SyncFiltersReader());
    }

    public function boot(Container $container): void
    {
        $container->get(ProjectFieldGroup::class)->register();
        $container->get(SyncDataFieldGroup::class)->register();
        $container->get(SyncFiltersFieldGroup::class)->register();
    }
}
