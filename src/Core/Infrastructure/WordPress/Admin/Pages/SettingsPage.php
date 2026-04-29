<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Pages;

/**
 * Renderiza la página de configuraciones (settings) del plugin.
 * 
 * @name SettingsPage
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Pages
 * @since 1.0.0
 */
class SettingsPage
{
    /**
     * Renderiza el contenido de la página de configuraciones.
     * 
     * @return void
     */
    public function render(): void
    {
        include WPDM_PATH . 'templates/admin/settings.php';
    }
}
