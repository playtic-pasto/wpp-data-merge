<?php

declare(strict_types=1);

namespace WPDM\Bootstrap;

use WPDM\Core\Infrastructure\Database\WPDM_Database;
use WPDM\Core\Infrastructure\WordPress\Cron\CronRunner;
use WPDM\Core\Infrastructure\WordPress\Cron\CronScheduler;
use WPDM\Core\Infrastructure\WordPress\Cron\CronSettings;
use WPDM\Core\Infrastructure\WordPress\Cron\CronLock;
use WPDM\Core\Infrastructure\WordPress\Cron\CronHistory;
use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\Api\CredentialLoader;
use WPDM\Core\Infrastructure\Api\HttpApiClient;
use WPDM\Core\Infrastructure\Api\SincoApiClient;
use WPDM\Core\Infrastructure\Api\TokenCacheService;
use WPDM\Core\Infrastructure\Api\WPDM_AuthService;
use WPDM\Shared\Encryption\WPDM_Encryption;
use WPDM\Shared\Helpers\UserHelper;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Acciones de ciclo de vida del plugin (activación/desactivación) invocadas
 * por los hooks register_activation_hook/register_deactivation_hook.
 *
 * Dado que los hooks corren antes de `plugins_loaded`, aquí construimos los
 * servicios mínimos manualmente sin pasar por el Container principal.
 *
 * @name Lifecycle
 * @package WPDM\Bootstrap
 * @since 1.0.0
 */
final class Lifecycle
{
    public static function activate(): void
    {
        WPDM_Database::migrate();

        $logger = new WPDM_Logger(WPDM_PATH);
        $logger->info('Plugin activado por: ' . UserHelper::getCurrentUserLabel());

        $settings = new CronSettings();
        if ($settings->isEnabled()) {
            self::buildScheduler($settings, $logger)->schedule();
        }
    }

    public static function deactivate(): void
    {
        $logger = new WPDM_Logger(WPDM_PATH);
        $logger->info('Plugin desactivado por: ' . UserHelper::getCurrentUserLabel());

        $settings = new CronSettings();
        self::buildScheduler($settings, $logger)->clear();
    }

    private static function buildScheduler(CronSettings $settings, WPDM_Logger $logger): CronScheduler
    {
        $encryption       = new WPDM_Encryption();
        $credentialLoader = new CredentialLoader($encryption);
        $tokenCache       = new TokenCacheService();
        $httpClient       = new HttpApiClient();
        $authService      = new WPDM_AuthService($credentialLoader, $tokenCache, $httpClient, $logger);
        $sinco            = new SincoApiClient($httpClient, $authService, $logger, $credentialLoader);
        $projects         = new ProjectsRepository();
        $syncService      = new ProjectSyncService($sinco, $projects, $logger);
        $lock             = new CronLock($settings);
        $history          = new CronHistory($settings);
        $runner           = new CronRunner($settings, $lock, $history, $syncService, $logger);

        return new CronScheduler($settings, $runner, $logger);
    }
}
