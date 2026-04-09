<?php
/**
 * Define la configuración del cron para el plugin WP Data Merge, incluyendo el hook que se utilizará para ejecutar las tareas programadas y el intervalo de tiempo en el que se ejecutarán dichas tareas.
 * @name cron.php
 * @package WPDM\Config
 * @since 1.0.0
 */

declare(strict_types=1);

return [
    'hook'     => 'wpdm_cron_sync',
    'interval' => 'hourly',
];
