<?php

declare(strict_types=1);

namespace WPDM\Bootstrap\Providers;

use WPDM\Bootstrap\ServiceProvider;
use WPDM\Core\Infrastructure\Api\CredentialLoader;
use WPDM\Core\Infrastructure\Api\HttpApiClient;
use WPDM\Core\Infrastructure\Api\TokenCacheService;
use WPDM\Core\Infrastructure\Api\WPDM_AuthService;
use WPDM\Shared\Container\Container;
use WPDM\Shared\Encryption\WPDM_Encryption;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Servicios de autenticación y cliente HTTP base.
 *
 * @name AuthProvider
 * @package WPDM\Bootstrap\Providers
 * @since 1.0.0
 */
class AuthProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(CredentialLoader::class, fn(Container $container) => new CredentialLoader($container->get(WPDM_Encryption::class)));
        $container->bind(TokenCacheService::class, fn() => new TokenCacheService());
        $container->bind(HttpApiClient::class, fn() => new HttpApiClient());

        $container->bind(WPDM_AuthService::class, fn(Container $container) => new WPDM_AuthService(
            $container->get(CredentialLoader::class),
            $container->get(TokenCacheService::class),
            $container->get(HttpApiClient::class),
            $container->get(WPDM_Logger::class)
        ));
    }

    public function boot(Container $container): void {}
}
