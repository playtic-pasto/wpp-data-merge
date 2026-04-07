<?php

namespace WPDM\Core\Infrastructure\WordPress\Admin;

use WPDM\Shared\Encryption\WPDM_Encryption;
use WPDM\Core\Infrastructure\Api\WPDM_AuthService;

class Menu
{

    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueueStyles']);
        add_action('admin_init', [$this, 'handleSettingsSave']);
        add_action('admin_init', [$this, 'handleTestConnection']);
    }

    public function addMenu(): void
    {
        add_menu_page(
            'WP Data Merge',
            'WP Data Merge',
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

    public function enqueueStyles(string $hook): void
    {
        if (strpos($hook, 'wpdm') === false) {
            return;
        }

        wp_enqueue_style(
            'wpdm-admin',
            WPDM_URL . 'assets/css/admin.css',
            [],
            WPDM_VERSION
        );
    }

    public function renderDashboard(): void
    {
        include WPDM_PATH . 'templates/admin/dashboard.php';
    }

    public function handleSettingsSave(): void
    {
        if (!isset($_POST['wpdm_save_settings'])) {
            return;
        }

        if (!check_admin_referer('wpdm_save_settings', 'wpdm_settings_nonce')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $endpoint = sanitize_url($_POST['wpdm_api_endpoint'] ?? '');
        update_option('wpdm_api_endpoint', $endpoint);

        $user = sanitize_text_field($_POST['wpdm_api_user'] ?? '');
        if (!empty($user)) {
            update_option('wpdm_api_user', WPDM_Encryption::encrypt($user));
        }

        $password = $_POST['wpdm_api_password'] ?? '';
        if (!empty($password)) {
            update_option('wpdm_api_password', WPDM_Encryption::encrypt($password));
        }

        wp_safe_redirect(admin_url('admin.php?page=wpdm-settings&wpdm_status=saved'));
        exit;
    }

    public function handleTestConnection(): void
    {
        if (!isset($_POST['wpdm_test_connection'])) {
            return;
        }

        if (!check_admin_referer('wpdm_test_connection', 'wpdm_test_nonce')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        $result = WPDM_AuthService::getToken(true);

        set_transient('wpdm_test_result', [
            'success' => $result['success'],
            'message' => $result['message'],
            'data'    => $result['data'] ?? [],
        ], 30);

        wp_safe_redirect(admin_url('admin.php?page=wpdm-settings&wpdm_tested=1'));
        exit;
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
