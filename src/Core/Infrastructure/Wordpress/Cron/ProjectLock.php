<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Cron;

/**
 * Lock atómico a nivel de proyecto individual usando INSERT IGNORE sobre wp_options.
 * Garantiza que sólo una sincronización (manual o automática) pueda ejecutarse
 * sobre un proyecto específico a la vez. Libera automáticamente los locks
 * huérfanos tras el TTL.
 *
 * @name ProjectLock
 * @package WPDM\Core\Infrastructure\WordPress\Cron
 * @since 1.0.0
 */
class ProjectLock
{
    private const OPTION_PREFIX = '_wpdm_sync_lock_';
    private const DEFAULT_TTL = 120; // 2 minutos (una sync de proyecto no debería tardar más)

    private int $postId;
    private int $ttl;

    public function __construct(int $postId, ?int $ttl = null)
    {
        $this->postId = $postId;
        $this->ttl = $ttl ?? self::DEFAULT_TTL;
    }

    /**
     * Intenta adquirir el lock para este proyecto. Devuelve true si lo consiguió,
     * false si otra ejecución está sincronizando el mismo proyecto.
     */
    public function acquire(): bool
    {
        global $wpdb;

        $table  = $wpdb->prefix . 'options';
        $option = $this->optionName();

        // Limpiar locks expirados (más viejos que el TTL).
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE option_name = %s AND option_value < %d",
            $option,
            \time() - $this->ttl
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
     * Verifica si existe un lock activo para este proyecto (sin intentar adquirirlo).
     */
    public function isLocked(): bool
    {
        global $wpdb;

        $table  = $wpdb->prefix . 'options';
        $option = $this->optionName();

        // Limpiar locks expirados primero.
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE option_name = %s AND option_value < %d",
            $option,
            \time() - $this->ttl
        ));

        // Verificar si existe un lock válido.
        $count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE option_name = %s",
            $option
        ));

        return (int) $count > 0;
    }

    /**
     * Actualiza el timestamp del lock (renovación mientras una sincronización larga sigue activa).
     */
    public function renew(): void
    {
        global $wpdb;
        $wpdb->update(
            $wpdb->prefix . 'options',
            ['option_value' => (string) \time()],
            ['option_name'  => $this->optionName()],
            ['%s'],
            ['%s']
        );
    }

    /**
     * Libera el lock de este proyecto.
     */
    public function release(): void
    {
        global $wpdb;
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name = %s",
            $this->optionName()
        ));
    }

    /**
     * Obtiene el nombre de la opción para el lock de este proyecto.
     */
    private function optionName(): string
    {
        return self::OPTION_PREFIX . $this->postId;
    }

    /**
     * Retorna el post ID asociado a este lock.
     */
    public function getPostId(): int
    {
        return $this->postId;
    }
}
