<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Api;

use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Cliente para consumir los endpoints del ERP SINCO (CBRClientes API).
 *
 * @name SincoApiClient
 * @package WPDM\Core\Infrastructure\Api
 * @since 1.0.0
 */
class SincoApiClient
{
    /** @var array<string, mixed> */
    private array $apiConfig;

    private HttpApiClient $httpClient;
    private WPDM_AuthService $authService;
    private CredentialLoader $credentialLoader;
    private WPDM_Logger $logger;

    public function __construct(
        ?HttpApiClient $httpClient = null,
        ?WPDM_AuthService $authService = null,
        ?WPDM_Logger $logger = null,
        ?CredentialLoader $credentialLoader = null
    ) {
        $this->apiConfig        = require WPDM_PATH . 'config/api.php';
        $this->httpClient       = $httpClient ?? new HttpApiClient();
        $this->authService      = $authService ?? new WPDM_AuthService();
        $this->credentialLoader = $credentialLoader ?? new CredentialLoader();
        $this->logger           = $logger ?? new WPDM_Logger(WPDM_PATH);
    }

    /**
     * Lista las unidades asociadas a un proyecto.
     *
     * @return array<int, array<string, mixed>>|\WP_Error
     */
    public function getUnidadesByProyecto(int|string $idProyecto): array|\WP_Error
    {
        return $this->requestList(strtr($this->apiConfig['units_path'], ['{id}' => (string) $idProyecto]));
    }

    /**
     * Lista los macroproyectos (información básica).
     *
     * @return array<int, array<string, mixed>>|\WP_Error
     */
    public function getMacroproyectos(): array|\WP_Error
    {
        return $this->requestList($this->apiConfig['macroprojects_path']);
    }

    /**
     * Lista los proyectos pertenecientes a un macroproyecto.
     *
     * @return array<int, array<string, mixed>>|\WP_Error
     */
    public function getProyectosByMacro(int|string $idMacro): array|\WP_Error
    {
        return $this->requestList(strtr($this->apiConfig['projects_path'], ['{id}' => (string) $idMacro]));
    }

    /**
     * Ejecuta un GET autenticado y devuelve el cuerpo decodificado.
     *
     * @return array<int|string, mixed>|\WP_Error
     */
    private function requestList(string $path): array|\WP_Error
    {
        $authHeader = $this->authService->getAuthorizationHeader();
        if ($authHeader === null) {
            return new \WP_Error('wpdm_auth_missing', 'No se pudo obtener el token de autenticación.');
        }

        $baseUrl = $this->credentialLoader->getBaseUrl();
        if ($baseUrl === '') {
            return new \WP_Error('wpdm_base_missing', 'Endpoint base no configurado.');
        }

        $url = $baseUrl . $this->apiConfig['api_base'] . $path;

        $response = $this->httpClient->get(
            $url,
            ['Authorization' => $authHeader],
            (int) $this->apiConfig['timeout']
        );

        if (is_wp_error($response)) {
            $this->logger->error('SINCO: ' . $response->get_error_message() . ' | URL: ' . $url);
            return $response;
        }

        $code = $this->httpClient->getResponseCode($response);
        $body = $this->httpClient->getResponseBody($response);

        if ($code === 401) {
            $this->authService->invalidateToken();
        }

        if ($code < 200 || $code >= 300) {
            $this->logger->error(sprintf('SINCO: HTTP %d | URL: %s | Body: %s', $code, $url, wp_json_encode($body)));
            return new \WP_Error('wpdm_http_error', "Error HTTP {$code} al consultar SINCO.");
        }

        return is_array($body) ? $body : [];
    }
}
