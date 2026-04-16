<?php

declare(strict_types=1);

namespace WPDM\Bootstrap\Providers;

use WPDM\Bootstrap\ServiceProvider;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\Api\CredentialLoader;
use WPDM\Core\Infrastructure\WordPress\Admin\Menu;
use WPDM\Core\Infrastructure\WordPress\Admin\PostType\Columns\SyncColumn;
use WPDM\Core\Infrastructure\WordPress\Admin\PostType\Controllers\SyncController;
use WPDM\Core\Infrastructure\WordPress\Admin\PostType\MetaBoxes\DataMetaBox;
use WPDM\Core\Infrastructure\WordPress\Admin\PostType\MetaBoxes\SyncMetaBox;
use WPDM\Core\Infrastructure\WordPress\Admin\PostType\ProjectAdminHooks;
use WPDM\Core\Infrastructure\WordPress\Hooks\Actions;
use WPDM\Core\Infrastructure\WordPress\Hooks\Resources;
use WPDM\Shared\Container\Container;

/**
 * Menú, hooks generales del admin y toda la experiencia del CPT proyecto
 * (columna, meta boxes, controller de sincronización).
 *
 * @name AdminProvider
 * @package WPDM\Bootstrap\Providers
 * @since 1.0.0
 */
class AdminProvider implements ServiceProvider
{
    public function register(Container $c): void
    {
        $c->bind(Menu::class, fn() => new Menu());
        $c->bind(Actions::class, fn() => new Actions());
        $c->bind(Resources::class, fn() => new Resources());

        $c->bind(SyncColumn::class, fn(Container $c) => new SyncColumn($c->get(CredentialLoader::class)));
        $c->bind(SyncMetaBox::class, fn(Container $c) => new SyncMetaBox($c->get(CredentialLoader::class)));
        $c->bind(DataMetaBox::class, fn() => new DataMetaBox());
        $c->bind(SyncController::class, fn(Container $c) => new SyncController($c->get(ProjectSyncService::class)));

        $c->bind(ProjectAdminHooks::class, fn(Container $c) => new ProjectAdminHooks(
            $c->get(SyncColumn::class),
            $c->get(SyncMetaBox::class),
            $c->get(DataMetaBox::class),
            $c->get(SyncController::class)
        ));
    }

    public function boot(Container $c): void
    {
        $c->get(Menu::class)->register();
        $c->get(Actions::class)->register();
        $c->get(Resources::class)->register();
        $c->get(ProjectAdminHooks::class)->register();
    }
}
