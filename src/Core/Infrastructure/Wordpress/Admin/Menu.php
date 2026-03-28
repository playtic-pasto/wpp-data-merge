<?php

namespace WPDM\Core\Infrastructure\WordPress\Admin;

class Menu
{

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    public function addMenu(): void
    {
        add_menu_page(
            'WPDM',
            'WP Data Merge',
            'manage_options',
            'wpdm-dashboard',
            [$this, 'renderDashboard']
        );
    }

    public function renderDashboard(): void
    {
        include \WPDM_PATH . 'templates/admin/dashboard.php';
    }
}
