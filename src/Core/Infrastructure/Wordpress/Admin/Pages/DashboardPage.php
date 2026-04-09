<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Pages;

/**
 * Renderiza la página de dashboard del plugin.
 * 
 * @name DashboardPage
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Pages
 * @since 1.0.0
 */
class DashboardPage
{
    /**
     * Renderiza el contenido de la página de dashboard.
     * 
     * @return void
     */
    public function render(): void
    {
        include WPDM_PATH . 'templates/admin/dashboard.php';
    }
}
