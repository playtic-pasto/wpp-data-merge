<?php

declare(strict_types=1);

namespace WPDM\Bootstrap\Providers;

use WPDM\Bootstrap\ServiceProvider;
use WPDM\Core\Infrastructure\WordPress\Cron\CronScheduler;
use WPDM\Shared\Container\Container;

/**
 * Programador de tareas Cron del plugin.
 *
 * @name CronProvider
 * @package WPDM\Bootstrap\Providers
 * @since 1.0.0
 */
class CronProvider implements ServiceProvider
{
    public function register(Container $c): void
    {
        $c->bind(CronScheduler::class, fn() => new CronScheduler());
    }

    public function boot(Container $c): void
    {
        $c->get(CronScheduler::class)->register();
    }
}
