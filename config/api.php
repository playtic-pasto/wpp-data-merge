<?php
/**
 * Configuración de conexión API para el plugin WP Data Merge.
 *
 * @name api.php
 * @package WPDM\Config
 * @since 1.0.0
 */

declare(strict_types=1);

return [
    'timeout'            => 30,
    'retry_limit'        => 3,
    'cache_ttl'          => 300,
    'auth_path'          => '/V3/API/Auth/Usuario',
    'api_base'           => '/V3/CBRClientes/API',
    'units_path'         => '/Unidades/PorProyecto/{id}',
    'unit_types_path'    => '/TipoUnidad',
    'macroprojects_path' => '/Macroproyectos/basica',
    'projects_path'      => '/proyectos/{id}',
    'option_endpoint'    => 'wpdm_api_endpoint',
    'option_user'        => 'wpdm_api_user',
    'option_password'    => 'wpdm_api_password',
];
