<?php

declare(strict_types=1);

namespace WPDM\Bootstrap\Providers;

use WPDM\Bootstrap\ServiceProvider;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\WordPress\Controllers\CronController;
use WPDM\Core\Infrastructure\WordPress\Cron\CronHistory;
use WPDM\Core\Infrastructure\WordPress\Cron\CronLock;
use WPDM\Core\Infrastructure\WordPress\Cron\CronRestController;
use WPDM\Core\Infrastructure\WordPress\Cron\CronRunner;
use WPDM\Core\Infrastructure\WordPress\Cron\CronScheduler;
use WPDM\Core\Infrastructure\WordPress\Cron\CronSettings;
use WPDM\Shared\Container\Container;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Bindings y hooks del Cron Job (settings, lock, history, scheduler, runner,
 * endpoint REST y controller admin-post).
 *
 * @name CronProvider
 * @package WPDM\Bootstrap\Providers
 * @since 1.0.0
 */
class CronProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(CronSettings::class, fn() => new CronSettings());
        $container->bind(CronLock::class, fn(Container $container) => new CronLock($container->get(CronSettings::class)));
        $container->bind(CronHistory::class, fn(Container $container) => new CronHistory($container->get(CronSettings::class)));

        $container->bind(CronRunner::class, fn(Container $container) => new CronRunner(
            $container->get(CronSettings::class),
            $container->get(CronLock::class),
            $container->get(CronHistory::class),
            $container->get(ProjectSyncService::class),
            $container->get(WPDM_Logger::class)
        ));

        $container->bind(CronScheduler::class, fn(Container $container) => new CronScheduler(
            $container->get(CronSettings::class),
            $container->get(CronRunner::class),
            $container->get(WPDM_Logger::class)
        ));

        $container->bind(CronRestController::class, fn(Container $container) => new CronRestController(
            $container->get(CronRunner::class)
        ));

        $container->bind(CronController::class, fn(Container $container) => new CronController(
            $container->get(CronSettings::class),
            $container->get(CronScheduler::class),
            $container->get(CronRunner::class),
            $container->get(CronHistory::class)
        ));
    }

    public function boot(Container $container): void
    {
        $container->get(CronScheduler::class)->register();
        $container->get(CronRestController::class)->register();
        $container->get(CronController::class)->register();
    }
}
