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
    public function register(Container $c): void
    {
        $c->bind(CronSettings::class, fn() => new CronSettings());
        $c->bind(CronLock::class, fn(Container $c) => new CronLock($c->get(CronSettings::class)));
        $c->bind(CronHistory::class, fn(Container $c) => new CronHistory($c->get(CronSettings::class)));

        $c->bind(CronRunner::class, fn(Container $c) => new CronRunner(
            $c->get(CronSettings::class),
            $c->get(CronLock::class),
            $c->get(CronHistory::class),
            $c->get(ProjectSyncService::class),
            $c->get(WPDM_Logger::class)
        ));

        $c->bind(CronScheduler::class, fn(Container $c) => new CronScheduler(
            $c->get(CronSettings::class),
            $c->get(CronRunner::class),
            $c->get(WPDM_Logger::class)
        ));

        $c->bind(CronRestController::class, fn(Container $c) => new CronRestController(
            $c->get(CronRunner::class)
        ));

        $c->bind(CronController::class, fn(Container $c) => new CronController(
            $c->get(CronSettings::class),
            $c->get(CronScheduler::class),
            $c->get(CronRunner::class),
            $c->get(CronHistory::class)
        ));
    }

    public function boot(Container $c): void
    {
        $c->get(CronScheduler::class)->register();
        $c->get(CronRestController::class)->register();
        $c->get(CronController::class)->register();
    }
}
