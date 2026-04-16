<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Cron;

/**
 * Lock atómico para el cron usando INSERT IGNORE sobre wp_options. Garantiza
 * que sólo una ejecución corra a la vez y libera automáticamente los locks
 * huérfanos tras el TTL.
 *
 * @name CronLock
 * @package WPDM\Core\Infrastructure\WordPress\Cron
 * @since 1.0.0
 */
class CronLock
{
    public function __construct(private CronSettings $settings) {}

    /**
     * Intenta adquirir el lock. Devuelve true si lo consiguió, false si otra
     * ejecución está corriendo.
     */
    public function acquire(): bool
    {
        global $wpdb;

        $table  = $wpdb->prefix . 'options';
        $option = $this->settings->optionLock();
        $ttl    = $this->settings->lockTtl();

        // Limpiar locks expirados (más viejos que el TTL).
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE option_name = %s AND option_value < %d",
            $option,
            \time() - $ttl
        ));

        // INSERT IGNORE: si ya existe la fila, no hace nada y retorna 0 filas afectadas.
        $inserted = $wpdb->query($wpdb->prepare(
            "INSERT IGNORE INTO {$table} (option_name, option_value, autoload) VALUES (%s, %d, 'no')",
            $option,
            \time()
        ));

        return (int) $inserted === 1;
    }

    /**
     * Actualiza el timestamp del lock (renovación mientras una ejecución larga sigue activa).
     */
    public function renew(): void
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'options',
            ['option_value' => (string) \time()],
            ['option_name'  => $this->settings->optionLock()],
            ['%s'],
            ['%s']
        );
    }

    /**
     * Libera el lock.
     */
    public function release(): void
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name = %s",
            $this->settings->optionLock()
        ));
    }
}
