<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Cron;

/**
 * Expone un endpoint REST que permite a un cron del sistema (wget/curl)
 * disparar la sincronización sin depender de WP-Cron. Autorización por token
 * compartido almacenado en opción.
 *
 * Endpoint: GET/POST /wp-json/wpdm/v1/cron/run?token=XXXX
 *
 * @name CronRestController
 * @package WPDM\Core\Infrastructure\WordPress\Cron
 * @since 1.0.0
 */
class CronRestController
{
    public const OPTION_TOKEN = 'wpdm_cron_api_token';
    private const NAMESPACE = 'wpdm/v1';
    private const ROUTE = '/cron/run';

    public function __construct(private CronRunner $runner) {}

    public function register(): void
    {
        \add_action('rest_api_init', [$this, 'registerRoute']);
    }

    public function registerRoute(): void
    {
        \register_rest_route(self::NAMESPACE, self::ROUTE, [
            'methods'             => ['GET', 'POST'],
            'callback'            => [$this, 'handle'],
            'permission_callback' => [$this, 'checkToken'],
        ]);
    }

    public function checkToken(\WP_REST_Request $request): bool|\WP_Error
    {
        $stored = (string) \get_option(self::OPTION_TOKEN, '');
        if ($stored === '') {
            return new \WP_Error('wpdm_no_token', 'API token no configurado.', ['status' => 503]);
        }

        $provided = (string) (
            $request->get_header('x-wpdm-token')
            ?: $request->get_param('token')
            ?: ''
        );

        if ($provided === '' || !\hash_equals($stored, $provided)) {
            return new \WP_Error('wpdm_invalid_token', 'Token inválido.', ['status' => 401]);
        }

        return true;
    }

    public function handle(\WP_REST_Request $request): \WP_REST_Response
    {
        $result = $this->runner->runApi();
        return new \WP_REST_Response($result, $result['executed'] ? 200 : 429);
    }

    /**
     * Genera (y persiste) un nuevo token aleatorio.
     */
    public static function regenerateToken(): string
    {
        $token = \wp_generate_password(40, false, false);
        \update_option(self::OPTION_TOKEN, $token, false);
        return $token;
    }

    public static function getToken(): string
    {
        return (string) \get_option(self::OPTION_TOKEN, '');
    }

    public static function getEndpointUrl(): string
    {
        return \rest_url(self::NAMESPACE . self::ROUTE);
    }
}
