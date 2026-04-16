<?php
/**
 * Configuración del Cron Job del plugin WP Data Merge.
 *
 * @name cron.php
 * @package WPDM\Config
 * @since 1.0.0
 */

declare(strict_types=1);

return [
    // Hook al que se engancha el runner automático.
    'hook'                    => 'wpdm_cron_sync',

    // ID del schedule personalizado (se registra dinámicamente en cron_schedules).
    'schedule_id'             => 'wpdm_dynamic_interval',

    // Intervalo por defecto en minutos (se puede sobreescribir por option).
    'default_interval_minutes' => 30,

    // TTL del lock atómico (segundos). Si una ejecución muere sin liberar, se recupera tras este tiempo.
    'lock_ttl'                => 300,

    // Claves de wp_options utilizadas por el cron.
    'option_enabled'          => 'wpdm_cron_enabled',
    'option_interval'         => 'wpdm_cron_interval_minutes',
    'option_last_run'         => 'wpdm_cron_last_run',
    'option_last_error'       => 'wpdm_cron_last_error',
    'option_history'          => 'wpdm_cron_history',
    'option_lock'             => '_wpdm_cron_running',

    // Transients para cooldown por tipo de ejecución.
    'cooldown_auto'           => 'wpdm_cron_auto_cooldown',
    'cooldown_manual'         => 'wpdm_cron_manual_cooldown',
    'cooldown_api'            => 'wpdm_cron_api_cooldown',

    // Tamaño máximo del historial (últimas N ejecuciones).
    'history_size'            => 20,
];
