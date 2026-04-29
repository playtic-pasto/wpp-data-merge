<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Controllers;

use WPDM\Shared\Encryption\WPDM_Encryption;
use WPDM\Core\Infrastructure\Api\WPDM_AuthService;
use WPDM\Shared\Logger\WPDM_Logger;
use WPDM\Shared\Helpers\UserHelper;

/**
 * Controlador responsable de gestionar el guardado de configuraciones de la API.
 * 
 * @name SettingsController
 * @package WPDM\Core\Infrastructure\WordPress\Controllers
 * @since 1.0.0
 */
class SettingsController
{
    /** @var array<string, string> */
    private array $apiConfig;

    private WPDM_Encryption $encryption;
    private WPDM_Logger $logger;
    private WPDM_AuthService $authService;

    public function __construct(
        ?WPDM_Encryption $encryption = null,
        ?WPDM_Logger $logger = null,
        ?WPDM_AuthService $authService = null
    ) {
        $this->apiConfig   = require WPDM_PATH . 'config/api.php';
        $this->encryption  = $encryption ?? new WPDM_Encryption();
        $this->logger      = $logger ?? new WPDM_Logger(WPDM_PATH);
        $this->authService = $authService ?? new WPDM_AuthService();
    }

    /**
     * Maneja la solicitud de guardado de configuraciones.
     * 
     * @return void
     */
    public function handleSave(): void
    {
        if (!$this->isAuthorizedRequest('wpdm_save_settings', 'wpdm_settings_nonce')) {
            return;
        }

        $this->saveEndpoint();
        $this->saveApiUser();
        $this->saveApiPassword();

        $this->authService->invalidateToken();
        $this->logger->info("Settings: token invalidado tras actualización por " . UserHelper::getCurrentUserLabel());

        wp_safe_redirect(admin_url('admin.php?page=wpdm-settings&wpdm_status=saved'));
        exit;
    }

    /**
     * Valida que la solicitud sea legítima, verificando el nonce y los permisos del usuario.
     * 
     * @param string $action El nombre de la acción que se espera en el POST.
     * @param string $nonce El nombre del campo nonce que se espera en el POST.
     * @return bool
     */
    private function isAuthorizedRequest(string $action, string $nonce): bool
    {
        return isset($_POST[$action])
            && (bool) check_admin_referer($action, $nonce)
            && current_user_can('manage_options');
    }

    /**
     * Guarda el endpoint de la API en las opciones de WordPress.
     * 
     * @return void
     */
    private function saveEndpoint(): void
    {
        $endpoint = sanitize_url($_POST['wpdm_api_endpoint'] ?? '');
        update_option($this->apiConfig['option_endpoint'], $endpoint);
        $this->logger->info("Settings: endpoint actualizado a '{$endpoint}' por " . UserHelper::getCurrentUserLabel());
    }

    /**
     * Guarda el usuario de la API en las opciones de WordPress, cifrándolo antes de almacenarlo.
     * 
     * @return void
     */
    private function saveApiUser(): void
    {
        $user = sanitize_text_field($_POST['wpdm_api_user'] ?? '');
        if (empty($user)) {
            return;
        }
        update_option($this->apiConfig['option_user'], $this->encryption->encryptValue($user));
        $this->logger->info("Settings: usuario API actualizado por " . UserHelper::getCurrentUserLabel());
    }

    /**
     * Guarda la contraseña de la API en las opciones de WordPress, cifrándola antes de almacenarla.
     * 
     * @return void
     */
    private function saveApiPassword(): void
    {
        $password = sanitize_textarea_field($_POST['wpdm_api_password'] ?? '');
        if (empty($password)) {
            return;
        }
        update_option($this->apiConfig['option_password'], $this->encryption->encryptValue($password));
        $this->logger->info("Settings: contraseña API actualizada por " . UserHelper::getCurrentUserLabel());
    }
}
