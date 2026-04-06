<?php

namespace WPDM\Core\Infrastructure\WordPress\Admin;

use WPDM\Shared\Encryption\WPDM_Encryption;

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

        $endpoint = get_option('wpdm_api_endpoint', '');
        $user = get_option('wpdm_api_user', '');
        $password = get_option('wpdm_api_password', '');

        if (empty($endpoint) || empty($user) || empty($password)) {
            set_transient('wpdm_test_result', [
                'success' => false,
                'message' => 'Faltan credenciales. Guarda el endpoint, usuario y contraseña primero.',
            ], 30);
            wp_safe_redirect(admin_url('admin.php?page=wpdm-settings&wpdm_tested=1'));
            exit;
        }

        $body = wp_json_encode([
            'NomUsuario'   => WPDM_Encryption::decrypt($user),
            'ClaveUsuario' => WPDM_Encryption::decrypt($password),
        ]);

        $response = wp_remote_post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => $body,
            'timeout' => 15,
        ]);

        if (is_wp_error($response)) {
            set_transient('wpdm_test_result', [
                'success' => false,
                'message' => 'Error de conexión: ' . $response->get_error_message(),
            ], 30);
            wp_safe_redirect(admin_url('admin.php?page=wpdm-settings&wpdm_tested=1'));
            exit;
        }

        $code = wp_remote_retrieve_response_code($response);
        $responseBody = json_decode(wp_remote_retrieve_body($response), true);

        if ($code === 200 && !empty($responseBody['access_token'])) {
            set_transient('wpdm_test_result', [
                'success' => true,
                'message' => 'Conexión exitosa.',
                'data'    => [
                    'token_type'  => $responseBody['token_type'] ?? '',
                    'expires_in'  => $responseBody['expires_in'] ?? '',
                    'usuario'     => $responseBody['data']['NomUsuario'] ?? '',
                    'id_usuario'  => $responseBody['data']['IdUsuario'] ?? '',
                ],
            ], 30);
        } else {
            $error_msg = $responseBody['message'] ?? $responseBody['title'] ?? "Respuesta HTTP {$code}";
            set_transient('wpdm_test_result', [
                'success' => false,
                'message' => 'Error de autenticación: ' . $error_msg,
            ], 30);
        }

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
