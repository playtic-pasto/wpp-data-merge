<?php
/**
 * Define la configuración general del plugin WP Data Merge, incluyendo el nombre, versión, dominio de texto, requisitos mínimos de PHP y WordPress, autor y slug del plugin.
 * @name plugin.php
 * @package WPDM\Config
 * @since 1.0.0
 */

declare(strict_types=1);

return [
    'name'        => 'WPP Data Merge',
    'version'     => WPDM_VERSION,
    'text_domain' => 'wpp-data-merge',
    'min_php'     => '8.1',
    'min_wp'      => '6.0',
    'author'      => 'PlayTIC',
    'slug'        => 'wpdm',
];
