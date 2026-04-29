<?php

declare(strict_types=1);

namespace WPDM\Bootstrap\Providers;

use WPDM\Bootstrap\ServiceProvider;
use WPDM\Shared\Container\Container;
use WPDM\Shared\Encryption\WPDM_Encryption;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Utilidades transversales (logger, cifrado).
 *
 * @name SharedProvider
 * @package WPDM\Bootstrap\Providers
 * @since 1.0.0
 */
class SharedProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(WPDM_Logger::class, fn() => new WPDM_Logger(WPDM_PATH));
        $container->bind(WPDM_Encryption::class, fn() => new WPDM_Encryption());
    }

    public function boot(Container $container): void {}
}
