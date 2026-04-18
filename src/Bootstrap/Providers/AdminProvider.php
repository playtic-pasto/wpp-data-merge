<?php

declare(strict_types=1);

namespace WPDM\Bootstrap\Providers;

use WPDM\Bootstrap\ServiceProvider;
use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\Api\CredentialLoader;
use WPDM\Core\Infrastructure\WordPress\Admin\Menu;
use WPDM\Core\Infrastructure\WordPress\Admin\Pages\CronPage;
use WPDM\Core\Infrastructure\WordPress\Cron\CronHistory;
use WPDM\Core\Infrastructure\WordPress\Cron\CronScheduler;
use WPDM\Core\Infrastructure\WordPress\Cron\CronSettings;
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
    public function register(Container $container): void
    {
        $container->bind(CronPage::class, fn(Container $container) => new CronPage(
            $container->get(CronSettings::class),
            $container->get(CronScheduler::class),
            $container->get(CronHistory::class),
            $container->get(ProjectsRepository::class)
        ));

        $container->bind(Menu::class, fn(Container $container) => new Menu(
            null,
            null,
            $container->get(CronPage::class),
            null
        ));
        $container->bind(Actions::class, fn() => new Actions());
        $container->bind(Resources::class, fn() => new Resources());

        $container->bind(SyncColumn::class, fn(Container $container) => new SyncColumn($container->get(CredentialLoader::class)));
        $container->bind(SyncMetaBox::class, fn(Container $container) => new SyncMetaBox($container->get(CredentialLoader::class)));
        $container->bind(DataMetaBox::class, fn() => new DataMetaBox());
        $container->bind(SyncController::class, fn(Container $container) => new SyncController($container->get(ProjectSyncService::class)));

        $container->bind(ProjectAdminHooks::class, fn(Container $container) => new ProjectAdminHooks(
            $container->get(SyncColumn::class),
            $container->get(SyncMetaBox::class),
            $container->get(DataMetaBox::class),
            $container->get(SyncController::class)
        ));
    }

    public function boot(Container $container): void
    {
        $container->get(Menu::class)->register();
        $container->get(Actions::class)->register();
        $container->get(Resources::class)->register();
        $container->get(ProjectAdminHooks::class)->register();
    }
}
