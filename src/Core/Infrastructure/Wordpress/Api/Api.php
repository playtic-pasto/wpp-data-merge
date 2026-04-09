<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Api;

use WPDM\Shared\Encryption\WPDM_Encryption;
use WPDM\Core\Infrastructure\Api\WPDM_AuthService;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * 
 * Permite interactuar con la API en WordPress para el plugin WP Data Merge, gestionando la autenticación y las configuraciones de la API.
 * 
 * @name Api
 * @package WPDM\Core\Infrastructure\WordPress\Api
 * @since 1.0.0
 */
class Api
{
    /** @var array<string, string> */
    private array $apiConfig;

    private WPDM_Logger $logger;

    public function __construct()
    {
        $this->apiConfig = require WPDM_PATH . 'config/api.php';
        $this->logger    = new WPDM_Logger(WPDM_PATH);
    }

    public function register(): void
    {
        add_action('admin_init', [$this, 'handleSettingsSave']);
        add_action('admin_init', [$this, 'handleTestConnection']);
    }

    /**
     * Valida que la solicitud de guardado de configuraciones o prueba de conexión sea legítima, verificando el nonce y los permisos del usuario.
     * 
     * @param string $action El nombre de la acción que se espera en el POST para identificar la solicitud 
     * @param string $nonce El nombre del campo nonce que se espera en el POST para verificar la autenticidad de la solicitud
     * @return bool Devuelve true si la solicitud es válida y el usuario tiene permisos, o false en caso contrario.
     * @access private
     */
    private function isAuthorizedRequest(string $action, string $nonce): bool
    {
        return isset($_POST[$action])
            && (bool) check_admin_referer($action, $nonce)
            && current_user_can('manage_options');
    }

    /**
     * Devuelve una etiqueta con el nombre de usuario y el ID del usuario actual.
     *
     * @return string La etiqueta del usuario actual.
     * @access private
     */
    private function currentUserTag(): string
    {
        $user = wp_get_current_user();
        return "{$user->user_login} (ID: {$user->ID})";
    }

    /**
     * Maneja la solicitud de guardado de configuraciones, validando la autenticidad de la solicitud, actualizando las opciones de la API y forzando la renovación del token de autenticación.
     * 
     * @return void
     * @access public
     */
    public function handleSettingsSave(): void
    {
        if (!$this->isAuthorizedRequest('wpdm_save_settings', 'wpdm_settings_nonce')) {
            return;
        }

        $this->saveEndpoint();
        $this->saveApiUser();
        $this->saveApiPassword();

        WPDM_AuthService::invalidateToken();
        $this->logger->info("Settings: token invalidado tras actualización por {$this->currentUserTag()}");

        wp_safe_redirect(admin_url('admin.php?page=wpdm-settings&wpdm_status=saved'));
        exit;
    }

    /**
     * Guarda el endpoint de la API en las opciones de WordPress, asegurándose de sanitizar la URL y registrando un mensaje en el log con la nueva configuración y el usuario que realizó la acción.
     * 
     * @return void
      * @access private
     */
    private function saveEndpoint(): void
    {
        $endpoint = sanitize_url($_POST['wpdm_api_endpoint'] ?? '');
        update_option($this->apiConfig['option_endpoint'], $endpoint);
        $this->logger->info("Settings: endpoint actualizado a '{$endpoint}' por {$this->currentUserTag()}");
    }

    /**
     * Guarda el usuario de la API en las opciones de WordPress, cifrándolo antes de almacenarlo y registrando un mensaje en el log indicando que el usuario ha sido actualizado, junto con la etiqueta del usuario que realizó la acción.
     * 
     * @return void
     * @access private
     */
    private function saveApiUser(): void
    {
        $user = sanitize_text_field($_POST['wpdm_api_user'] ?? '');
        if (empty($user)) {
            return;
        }
        update_option($this->apiConfig['option_user'], WPDM_Encryption::encrypt($user));
        $this->logger->info("Settings: usuario API actualizado por {$this->currentUserTag()}");
    }

    /**
     * Guarda la contraseña de la API en las opciones de WordPress, cifrándola antes de almacenarla y registrando un mensaje en el log indicando que la contraseña ha sido actualizada, junto con la etiqueta del usuario que realizó la acción.
     * 
     * @return void
     * @access private
     */
    private function saveApiPassword(): void
    {
        $password = $_POST['wpdm_api_password'] ?? '';
        if (empty($password)) {
            return;
        }
        update_option($this->apiConfig['option_password'], WPDM_Encryption::encrypt($password));
        $this->logger->info("Settings: contraseña API actualizada por {$this->currentUserTag()}");
    }

    /**
     * Maneja la solicitud de prueba de conexión a la API, validando la autenticidad de la solicitud, obteniendo un nuevo token de autenticación forzado y almacenando el resultado en una transiente para mostrarlo en la interfaz de usuario, luego redirige de vuelta a la página de ajustes con un indicador de que se realizó la prueba.
     * 
     * @return void
     * @access public
     */
    public function handleTestConnection(): void
    {
        if (!$this->isAuthorizedRequest('wpdm_test_connection', 'wpdm_test_nonce')) {
            return;
        }

        $result = WPDM_AuthService::getToken(true);

        set_transient('wpdm_test_result', $result->toArray(), 30);

        wp_safe_redirect(admin_url('admin.php?page=wpdm-settings&wpdm_tested=1'));
        exit;
    }
}
