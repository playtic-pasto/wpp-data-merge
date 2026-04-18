<?php
//TODO: AQUI ES PARA REGISTRAR LOS ACF

declare(strict_types=1);

namespace WPDM\Bootstrap\Providers;

use WPDM\Bootstrap\ServiceProvider;
use WPDM\Core\Infrastructure\WordPress\Acf\ProjectFieldGroup;
use WPDM\Shared\Container\Container;

/**
 * Registra todos los grupos de campos ACF del plugin.
 *
 * Cada grupo se define en su propia clase dentro de Infrastructure\WordPress\Acf\
 * y se conecta al hook 'acf/init' desde este provider.
 *
 * Clases registradas:
 * @see ProjectFieldGroup  Campos del CPT "proyecto" (id_macroproject, ids_project).
 */
class AcfProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(ProjectFieldGroup::class, fn() => new ProjectFieldGroup());
    }

    public function boot(Container $container): void
    {
        $container->get(ProjectFieldGroup::class)->register();
    }
}
