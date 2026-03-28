<?php

namespace WPDM\Core\Infrastructure\WordPress\Cron;

class CronScheduler
{

    public function register(): void
    {
        add_action('wpdm_cron_sync', [$this, 'execute']);
    }

    public function schedule(): void
    {
        if (!wp_next_scheduled('wpdm_cron_sync')) {
            wp_schedule_event(time(), 'hourly', 'wpdm_cron_sync');
        }
    }

    public function execute(): void
    {
        // ejecutar caso de uso
    }
}
