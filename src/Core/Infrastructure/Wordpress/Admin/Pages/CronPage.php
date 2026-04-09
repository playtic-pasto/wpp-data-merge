<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Pages;

/**
 * Renderiza la página de configuración del cron del plugin.
 * 
 * @name CronPage
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Pages
 * @since 1.0.0
 */
class CronPage
{
    /**
     * Renderiza el contenido de la página de cron.
     * 
     * @return void
     */
    public function render(): void
    {
        include WPDM_PATH . 'templates/admin/cron.php';
    }
}
