<?php
/**
 * Define la configuración de la conexión API para el plugin WP Data Merge, incluyendo parámetros como el tiempo de espera, límite de reintentos, tiempo de vida de la caché y las opciones de WordPress donde se almacenan los detalles de la conexión API.
 * @name api.php
 * @package WPDM\Config
 * @since 1.0.0
 */

declare(strict_types=1);

return [
    'timeout'         => 30,
    'retry_limit'     => 3,
    'cache_ttl'       => 300,
    'auth_path'       => '/V3/API/Auth/Usuario',
    'option_endpoint' => 'wpdm_api_endpoint',
    'option_user'     => 'wpdm_api_user',
    'option_password' => 'wpdm_api_password',
];
