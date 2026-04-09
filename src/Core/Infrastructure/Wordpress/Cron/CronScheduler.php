<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Cron;

use WPDM\Shared\Encryption\WPDM_Encryption;
use WPDM\Core\Infrastructure\Api\WPDM_AuthService;

/**
 * 
 * Permite programar tareas cron en WordPress para el plugin WP Data Merge, gestionando la programación, ejecución y validación de las tareas programadas.
 * 
 * @name CronScheduler
 * @package WPDM\Core\Infrastructure\WordPress\Cron
 * @since 1.0.0
 */
class CronScheduler
{
    /** @var array<string, string> */
    private array $cronConfig;

    public function __construct()
    {
        $this->cronConfig = require WPDM_PATH . 'config/cron.php';
    }

    public function register(): void
    {
        add_action($this->cronConfig['hook'], [$this, 'validateCron']);
    }

    public function schedule(): void
    {
        if (!wp_next_scheduled($this->cronConfig['hook'])) {
            wp_schedule_event(time(), $this->cronConfig['interval'], $this->cronConfig['hook']);
        }
    }

    public function validateCron(): void
    {
        // ejecutar caso de uso
    }


}
