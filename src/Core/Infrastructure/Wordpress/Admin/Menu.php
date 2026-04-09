<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin;

/**
 * Permite agregar un menú personalizado en el panel de administración de WordPress para gestionar las funcionalidades del plugin WP Data Merge.
 * 
 * @name Menu
 * @package WPDM\Core\Infrastructure\WordPress\Admin
 * @since 1.0.0

 */
class Menu
{
    /** @var array<string, string> */
    private array $pluginConfig;

    public function __construct()
    {
        $this->pluginConfig = require WPDM_PATH . 'config/plugin.php';
    }

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function addMenu(): void
    {
        add_menu_page(
            $this->pluginConfig['name'],
            $this->pluginConfig['name'],
            'manage_options',
            'wpdm-dashboard',
            [$this, 'renderDashboard'],
            'dashicons-database',
            80
        );

        add_submenu_page(
            'wpdm-dashboard',
            'Panel Principal — WP Data Merge',
            'Panel Principal',
            'manage_options',
            'wpdm-dashboard',
            [$this, 'renderDashboard']
        );

        add_submenu_page(
            'wpdm-dashboard',
            'Ajustes de Conexión — WP Data Merge',
            'Conexión API',
            'manage_options',
            'wpdm-settings',
            [$this, 'renderSettings']
        );

        add_submenu_page(
            'wpdm-dashboard',
            'Cron Job — WP Data Merge',
            'Cron Job',
            'manage_options',
            'wpdm-cron',
            [$this, 'renderCron']
        );

        add_submenu_page(
            'wpdm-dashboard',
            'Proyectos — WP Data Merge',
            'Proyectos',
            'manage_options',
            'wpdm-projects',
            [$this, 'renderProjects']
        );

        add_submenu_page(
            'wpdm-dashboard',
            'Soporte — WP Data Merge',
            'Soporte',
            'manage_options',
            'wpdm-support',
            [$this, 'renderSupport']
        );
    }

    public function renderDashboard(): void
    {
        include WPDM_PATH . 'templates/admin/dashboard.php';
    }

    public function renderSettings(): void
    {
        include WPDM_PATH . 'templates/admin/settings.php';
    }

    public function renderCron(): void
    {
        include WPDM_PATH . 'templates/admin/cron.php';
    }

    public function renderProjects(): void
    {
        include WPDM_PATH . 'templates/admin/projects.php';
    }

    public function renderSupport(): void
    {
        include WPDM_PATH . 'templates/admin/support.php';
    }
}
