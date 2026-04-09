<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Controllers;

use WPDM\Core\Infrastructure\Api\WPDM_AuthService;

/**
 * Controlador responsable de gestionar las pruebas de conexión con la API.
 * 
 * @name ConnectionTestController
 * @package WPDM\Core\Infrastructure\WordPress\Controllers
 * @since 1.0.0
 */
class ConnectionTestController
{
    private WPDM_AuthService $authService;

    public function __construct(?WPDM_AuthService $authService = null)
    {
        $this->authService = $authService ?? new WPDM_AuthService();
    }

    /**
     * Maneja la solicitud de prueba de conexión a la API.
     * 
     * @return void
     */
    public function handleTest(): void
    {
        if (!$this->isAuthorizedRequest('wpdm_test_connection', 'wpdm_test_nonce')) {
            return;
        }

        $result = $this->authService->getToken(true);

        set_transient('wpdm_test_result', $result->toArray(), 30);

        wp_safe_redirect(admin_url('admin.php?page=wpdm-settings&wpdm_tested=1'));
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
}
