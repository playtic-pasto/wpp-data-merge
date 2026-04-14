<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Database;

/**
 * Gestiona las migraciones de la base de datos del plugin. Crea las tablas
 * necesarias para almacenar proyectos y unidades sincronizados desde SINCO.
 *
 * @name WPDM_Database
 * @package WPDM\Core\Infrastructure\Database
 * @since 1.0.0
 */
class WPDM_Database
{
    public const TABLE_PROJECTS = 'wpdm_projects';
    public const TABLE_UNITS    = 'wpdm_units';

    public const SYNC_STATUS_ACTIVE  = 'active';
    public const SYNC_STATUS_PENDING = 'pending';
    public const SYNC_STATUS_ERROR   = 'error';

    /** @var list<string> */
    public const SYNC_STATUSES = [
        self::SYNC_STATUS_ACTIVE,
        self::SYNC_STATUS_PENDING,
        self::SYNC_STATUS_ERROR,
    ];

    /**
     * Ejecuta las migraciones idempotentes vía dbDelta.
     */
    public static function migrate(): void
    {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset       = $wpdb->get_charset_collate();
        $projectsTable = $wpdb->prefix . self::TABLE_PROJECTS;
        $unitsTable    = $wpdb->prefix . self::TABLE_UNITS;

        $projectsSql = "CREATE TABLE {$projectsTable} (
            project_id BIGINT UNSIGNED NOT NULL,
            macroproject_id BIGINT UNSIGNED NULL,
            name VARCHAR(191) NULL,
            wp_post_id BIGINT UNSIGNED NULL,
            sync_status ENUM('active','pending','error') NOT NULL DEFAULT 'active',
            last_error TEXT NULL,
            units_count INT UNSIGNED NOT NULL DEFAULT 0,
            last_synced_at DATETIME NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (project_id),
            KEY idx_macroproject (macroproject_id),
            KEY idx_sync_status (sync_status),
            KEY idx_wp_post (wp_post_id)
        ) {$charset};";

        $unitsSql = "CREATE TABLE {$unitsTable} (
            id BIGINT UNSIGNED NOT NULL,
            project_id BIGINT UNSIGNED NOT NULL,
            macroproject_id BIGINT UNSIGNED NULL,
            name VARCHAR(100) NULL,
            unit_type VARCHAR(80) NULL,
            property_type VARCHAR(80) NULL,
            status VARCHAR(50) NULL,
            price DECIMAL(14,2) NULL,
            private_area DECIMAL(10,2) NULL,
            built_area DECIMAL(10,2) NULL,
            floor_number INT NULL,
            delivery_date DATETIME NULL,
            is_main TINYINT(1) NOT NULL DEFAULT 0,
            is_blocked TINYINT(1) NOT NULL DEFAULT 0,
            raw_data LONGTEXT NULL,
            sync_status ENUM('active','pending','error') NOT NULL DEFAULT 'active',
            last_error TEXT NULL,
            synced_at DATETIME NULL,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            deleted_at DATETIME NULL,
            PRIMARY KEY  (id),
            KEY idx_project (project_id),
            KEY idx_macroproject (macroproject_id),
            KEY idx_status (status),
            KEY idx_unit_type (unit_type),
            KEY idx_price (price),
            KEY idx_delivery_date (delivery_date),
            KEY idx_sync_status (sync_status),
            KEY idx_deleted (deleted_at),
            KEY idx_name (name)
        ) {$charset};";

        \dbDelta($projectsSql);
        \dbDelta($unitsSql);
    }

    /**
     * Devuelve true si el valor es un sync_status válido.
     */
    public static function isValidSyncStatus(string $status): bool
    {
        return in_array($status, self::SYNC_STATUSES, true);
    }
}
