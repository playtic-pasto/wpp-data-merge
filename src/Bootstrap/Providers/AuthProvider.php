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
    public function register(Container $c): void
    {
        $c->bind(CredentialLoader::class, fn(Container $c) => new CredentialLoader($c->get(WPDM_Encryption::class)));
        $c->bind(TokenCacheService::class, fn() => new TokenCacheService());
        $c->bind(HttpApiClient::class, fn() => new HttpApiClient());

        $c->bind(WPDM_AuthService::class, fn(Container $c) => new WPDM_AuthService(
            $c->get(CredentialLoader::class),
            $c->get(TokenCacheService::class),
            $c->get(HttpApiClient::class),
            $c->get(WPDM_Logger::class)
        ));
    }

    public function boot(Container $c): void {}
}
