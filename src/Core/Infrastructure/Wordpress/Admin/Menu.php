<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin;

use WPDM\Core\Infrastructure\WordPress\Admin\Pages\DashboardPage;
use WPDM\Core\Infrastructure\WordPress\Admin\Pages\SettingsPage;
use WPDM\Core\Infrastructure\WordPress\Admin\Pages\CronPage;
use WPDM\Core\Infrastructure\WordPress\Admin\Pages\ProjectsPage;
use WPDM\Core\Infrastructure\WordPress\Admin\Pages\SupportPage;

/**
 * Registra el menú de administración del plugin en WordPress.
 * 
 * @name Menu
 * @package WPDM\Core\Infrastructure\WordPress\Admin
 * @since 1.0.0
 */
class Menu
{
    /** @var array<string, string> */
    private array $pluginConfig;

    private DashboardPage $dashboardPage;
    private SettingsPage $settingsPage;
    private CronPage $cronPage;
    private ProjectsPage $projectsPage;
    private SupportPage $supportPage;

    public function __construct(
        ?DashboardPage $dashboardPage = null,
        ?SettingsPage $settingsPage = null,
        ?CronPage $cronPage = null,
        ?ProjectsPage $projectsPage = null,
        ?SupportPage $supportPage = null
    ) {
        $this->pluginConfig  = require WPDM_PATH . 'config/plugin.php';
        $this->dashboardPage = $dashboardPage ?? new DashboardPage();
        $this->settingsPage  = $settingsPage ?? new SettingsPage();
        $this->cronPage      = $cronPage ?? new CronPage();
        $this->projectsPage  = $projectsPage ?? new ProjectsPage();
        $this->supportPage   = $supportPage ?? new SupportPage();
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
            [$this->dashboardPage, 'render'],
            'dashicons-database',
            80
        );

        add_submenu_page(
            'wpdm-dashboard',
            'Panel Principal — WP Data Merge',
            'Panel Principal',
            'manage_options',
            'wpdm-dashboard',
            [$this->dashboardPage, 'render']
        );

        add_submenu_page(
            'wpdm-dashboard',
            'Ajustes de Conexión — WP Data Merge',
            'Conexión API',
            'manage_options',
            'wpdm-settings',
            [$this->settingsPage, 'render']
        );

        add_submenu_page(
            'wpdm-dashboard',
            'Cron Job — WP Data Merge',
            'Cron Job',
            'manage_options',
            'wpdm-cron',
            [$this->cronPage, 'render']
        );

        add_submenu_page(
            'wpdm-dashboard',
            'Proyectos — WP Data Merge',
            'Proyectos',
            'manage_options',
            'wpdm-projects',
            [$this->projectsPage, 'render']
        );

        add_submenu_page(
            'wpdm-dashboard',
            'Soporte — WP Data Merge',
            'Soporte',
            'manage_options',
            'wpdm-support',
            [$this->supportPage, 'render']
        );
    }
}
