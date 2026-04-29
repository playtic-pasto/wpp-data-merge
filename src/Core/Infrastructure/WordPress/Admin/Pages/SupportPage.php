<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Pages;

/**
 * Renderiza la página de soporte del plugin.
 * 
 * @name SupportPage
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Pages
 * @since 1.0.0
 */
class SupportPage
{
    /**
     * Renderiza el contenido de la página de soporte.
     * 
     * @return void
     */
    public function render(): void
    {
        include WPDM_PATH . 'templates/admin/support.php';
    }
}
