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
    public function register(Container $container): void
    {
        $container->bind(SincoApiClient::class, fn(Container $container) => new SincoApiClient(
            $container->get(HttpApiClient::class),
            $container->get(WPDM_AuthService::class),
            $container->get(WPDM_Logger::class),
            $container->get(CredentialLoader::class)
        ));

        $container->bind(ProjectsRepository::class, fn() => new ProjectsRepository());

        $container->bind(ProjectSyncService::class, fn(Container $container) => new ProjectSyncService(
            $container->get(SincoApiClient::class),
            $container->get(ProjectsRepository::class),
            $container->get(WPDM_Logger::class)
        ));
    }

    public function boot(Container $container): void {}
}
