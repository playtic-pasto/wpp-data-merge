<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Api;

use WPDM\Core\Infrastructure\WordPress\Controllers\SettingsController;
use WPDM\Core\Infrastructure\WordPress\Controllers\ConnectionTestController;

/**
 * Registra los hooks relacionados con la API y delega las acciones a los controllers correspondientes.
 * 
 * @name Api
 * @package WPDM\Core\Infrastructure\WordPress\Api
 * @since 1.0.0
 */
class Api
{
    private SettingsController $settingsController;
    private ConnectionTestController $connectionTestController;

    public function __construct(
        ?SettingsController $settingsController = null,
        ?ConnectionTestController $connectionTestController = null
    ) {
        $this->settingsController       = $settingsController ?? new SettingsController();
        $this->connectionTestController = $connectionTestController ?? new ConnectionTestController();
    }

    /**
     * Registra los hooks de admin_init para manejar configuraciones y pruebas de conexión.
     * 
     * @return void
     */
    public function register(): void
    {
        add_action('admin_init', [$this->settingsController, 'handleSave']);
        add_action('admin_init', [$this->connectionTestController, 'handleTest']);
    }
}
