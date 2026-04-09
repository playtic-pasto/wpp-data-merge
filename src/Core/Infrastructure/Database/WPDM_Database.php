<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Database;

/**
 * Permite gestionar la base de datos para el plugin WP Data Merge, incluyendo la creación de tablas necesarias para almacenar los datos importados desde el endpoint externo.
 * 
 * @name WPDM_Database
 * @package WPDM\Core\Infrastructure\Database
 * @since 1.0.0
 */
class WPDM_Database
{

    /**
     * Ejecuta las migraciones necesarias para crear las tablas de la base de datos utilizadas por el plugin WP Data Merge.
     * @return void
     * @access public
     */
    public static function migrate(): void
    {
        global $wpdb;

        $table = $wpdb->prefix . 'wpdm_data';

        $sql = "CREATE TABLE $table (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            external_id VARCHAR(100) NOT NULL,
            data LONGTEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
