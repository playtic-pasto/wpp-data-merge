<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Pages;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\Api\CredentialLoader;
use WPDM\Core\Infrastructure\WordPress\Cron\CronSettings;
use WPDM\Core\Infrastructure\WordPress\Cron\CronHistory;

/**
 * Renderiza la página de dashboard del plugin con datos reales.
 *
 * Muestra:
 *  - Estadísticas de proyectos (total, sincronizados, con error, pendientes).
 *  - Estado de la conexión API y del cron job.
 *  - Historial de las últimas sincronizaciones.
 *
 * @see ProjectsRepository  Obtiene la lista de proyectos.
 * @see CronSettings        Lee el estado del cron.
 * @see CronHistory         Lee el historial de ejecuciones.
 * @see CredentialLoader     Verifica si la API está configurada.
 */
class DashboardPage
{
    public function __construct(
        private ProjectsRepository $projects,
        private CronSettings $cronSettings,
        private CronHistory $cronHistory,
        private CredentialLoader $credentials,
    ) {}

    public function render(): void
    {
        $data = $this->collectData();
        include WPDM_PATH . 'templates/admin/dashboard.php';
    }

    /**
     * Recopila todos los datos necesarios para el template.
     *
     * @return array<string, mixed>
     */
    private function collectData(): array
    {
        $allProjects = $this->projects->all();
        $totalProjects = count($allProjects);

        $configured = 0;
        $synced = 0;
        $withErrors = 0;
        $pending = 0;
        $lastSyncDate = null;

        foreach ($allProjects as $project) {
            $postId = $project['post_id'];

            if (empty($project['id_projects'])) {
                continue;
            }

            $configured++;

            $status = (string) get_post_meta($postId, ProjectSyncService::META_SYNC_STATUS, true);
            $lastSync = (string) get_post_meta($postId, ProjectSyncService::META_LAST_SYNCED, true);

            switch ($status) {
                case 'active':
                    $synced++;
                    break;
                case 'error':
                    $withErrors++;
                    break;
                case 'pending':
                    $pending++;
                    break;
            }

            if ($lastSync && ($lastSyncDate === null || $lastSync > $lastSyncDate)) {
                $lastSyncDate = $lastSync;
            }
        }

        $apiConfigured = $this->credentials->getBaseUrl() !== '';
        $cronEnabled = $this->cronSettings->isEnabled();
        $cronInterval = $this->cronSettings->intervalMinutes();
        $cronLastRun = get_option($this->cronSettings->optionLastRun(), null);
        $cronLastError = get_option($this->cronSettings->optionLastError(), '');
        $history = array_reverse($this->cronHistory->all());

        return [
            'total_projects'  => $totalProjects,
            'configured'      => $configured,
            'synced'          => $synced,
            'with_errors'     => $withErrors,
            'pending'         => $pending,
            'last_sync_date'  => $lastSyncDate,
            'api_configured'  => $apiConfigured,
            'cron_enabled'    => $cronEnabled,
            'cron_interval'   => $cronInterval,
            'cron_last_run'   => $cronLastRun,
            'cron_last_error' => $cronLastError,
            'history'         => array_slice($history, 0, 5),
        ];
    }
}
