<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Pages;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\WordPress\Controllers\CronController;
use WPDM\Core\Infrastructure\WordPress\Cron\CronHistory;
use WPDM\Core\Infrastructure\WordPress\Cron\CronRestController;
use WPDM\Core\Infrastructure\WordPress\Cron\CronScheduler;
use WPDM\Core\Infrastructure\WordPress\Cron\CronSettings;

/**
 * Página "Cron Job": configuración, ejecución manual, historial, endpoint
 * REST y errores recientes por proyecto.
 *
 * @name CronPage
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Pages
 * @since 1.0.0
 */
class CronPage
{
    public function __construct(
        private ?CronSettings $settings = null,
        private ?CronScheduler $scheduler = null,
        private ?CronHistory $history = null,
        private ?ProjectsRepository $projects = null,
    ) {
        $this->settings  ??= new CronSettings();
        $this->scheduler ??= null; // inyectado por el container
        $this->history   ??= new CronHistory($this->settings);
        $this->projects  ??= new ProjectsRepository();
    }

    public function render(): void
    {
        $settings   = $this->settings;
        $nextRun    = $this->scheduler?->nextRunTimestamp();
        $history    = $this->history->all();
        $lastRun    = (int) \get_option($settings->optionLastRun(), 0);
        $lastError  = (string) \get_option($settings->optionLastError(), '');
        $token      = CronRestController::getToken();
        $endpoint   = CronRestController::getEndpointUrl();
        $projects   = $this->projects->all();
        $errors     = [];
        foreach ($projects as $p) {
            $err = (string) \get_post_meta($p['post_id'], ProjectSyncService::META_LAST_ERROR, true);
            if ($err !== '') {
                $errors[] = ['project' => $p, 'error' => $err];
            }
        }
        $actions = [
            'save'  => CronController::ACTION_SAVE,
            'run'   => CronController::ACTION_RUN,
            'token' => CronController::ACTION_TOKEN,
            'clear' => CronController::ACTION_CLEAR,
        ];

        include WPDM_PATH . 'templates/admin/cron.php';
    }
}
