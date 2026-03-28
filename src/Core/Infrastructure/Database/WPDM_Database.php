<?php

namespace WPDM\Core\Infrastructure\Database;

class WPDM_Database
{

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
