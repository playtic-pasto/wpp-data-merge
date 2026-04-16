<?php

declare(strict_types=1);

namespace WPDM\Bootstrap\Providers;

use WPDM\Bootstrap\ServiceProvider;
use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\Api\CredentialLoader;
use WPDM\Core\Infrastructure\Api\HttpApiClient;
use WPDM\Core\Infrastructure\Api\SincoApiClient;
use WPDM\Core\Infrastructure\Api\WPDM_AuthService;
use WPDM\Shared\Container\Container;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Cliente SINCO, repositorio de proyectos y servicio de sincronización.
 *
 * @name SincoProvider
 * @package WPDM\Bootstrap\Providers
 * @since 1.0.0
 */
class SincoProvider implements ServiceProvider
{
    public function register(Container $c): void
    {
        $c->bind(SincoApiClient::class, fn(Container $c) => new SincoApiClient(
            $c->get(HttpApiClient::class),
            $c->get(WPDM_AuthService::class),
            $c->get(WPDM_Logger::class),
            $c->get(CredentialLoader::class)
        ));

        $c->bind(ProjectsRepository::class, fn() => new ProjectsRepository());

        $c->bind(ProjectSyncService::class, fn(Container $c) => new ProjectSyncService(
            $c->get(SincoApiClient::class),
            $c->get(ProjectsRepository::class),
            $c->get(WPDM_Logger::class)
        ));
    }

    public function boot(Container $c): void {}
}
