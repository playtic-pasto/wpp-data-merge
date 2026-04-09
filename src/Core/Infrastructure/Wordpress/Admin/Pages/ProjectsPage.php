<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Pages;

/**
 * Renderiza la página de proyectos del plugin.
 * 
 * @name ProjectsPage
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Pages
 * @since 1.0.0
 */
class ProjectsPage
{
    /**
     * Renderiza el contenido de la página de proyectos.
     * 
     * @return void
     */
    public function render(): void
    {
        include WPDM_PATH . 'templates/admin/projects.php';
    }
}
