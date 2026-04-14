<?php

declare(strict_types=1);

namespace WPDM\Core\Domain\Sync;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Infrastructure\Api\SincoApiClient;
use WPDM\Core\Infrastructure\Database\WPDM_Database;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Orquesta la sincronización de unidades entre SINCO y la BD local.
 *
 * @name SyncService
 * @package WPDM\Core\Domain\Sync
 * @since 1.0.0
 */
class SyncService
{
    private SincoApiClient $sinco;
    private SyncRepository $repository;
    private ProjectsRepository $projectsCpt;
    private WPDM_Logger $logger;

    public function __construct(
        ?SincoApiClient $sinco = null,
        ?SyncRepository $repository = null,
        ?ProjectsRepository $projectsCpt = null,
        ?WPDM_Logger $logger = null
    ) {
        $this->sinco       = $sinco ?? new SincoApiClient();
        $this->repository  = $repository ?? new SyncRepository();
        $this->projectsCpt = $projectsCpt ?? new ProjectsRepository();
        $this->logger      = $logger ?? new WPDM_Logger(WPDM_PATH);
    }

    /**
     * Sincroniza las unidades indicadas (por id SINCO). Si $unitIds está vacío para un
     * proyecto, no hace nada para ese proyecto.
     *
     * @param int[] $unitIds
     * @return array{synced:int, failed:int, errors:list<string>}
     */
    public function syncUnits(array $unitIds): array
    {
        $result = ['synced' => 0, 'failed' => 0, 'errors' => []];
        if (empty($unitIds)) {
            return $result;
        }

        $unitIds = array_values(array_unique(array_map('intval', $unitIds)));

        foreach ($this->projectsCpt->all() as $project) {
            foreach ($project['id_proyectos'] as $idProyecto) {
                $context = $this->contextFor($project, $idProyecto);

                $projectUnits = $this->sinco->getUnidadesByProyecto($idProyecto);
                if (is_wp_error($projectUnits)) {
                    $msg = "Proyecto {$idProyecto}: " . $projectUnits->get_error_message();
                    $this->logger->error("Sync: {$msg}");
                    $this->repository->ensureProject($idProyecto, $context['macro_id'], $context['name'], $context['wp_post_id']);
                    $this->repository->updateProjectSync($idProyecto, WPDM_Database::SYNC_STATUS_ERROR, $projectUnits->get_error_message());
                    $result['errors'][] = $msg;
                    continue;
                }

                $matched = array_values(array_filter($projectUnits, static function ($u) use ($unitIds) {
                    return in_array((int) ($u['id'] ?? 0), $unitIds, true);
                }));

                if (empty($matched)) {
                    continue;
                }

                $this->repository->ensureProject($idProyecto, $context['macro_id'], $context['name'], $context['wp_post_id']);

                foreach ($matched as $unit) {
                    try {
                        $this->repository->upsertUnit($unit, $idProyecto, $context['macro_id']);
                        $result['synced']++;
                    } catch (\Throwable $e) {
                        $this->repository->markUnitError((int) $unit['id'], $e->getMessage());
                        $result['failed']++;
                        $result['errors'][] = "Unidad {$unit['id']}: " . $e->getMessage();
                        $this->logger->error("Sync unit {$unit['id']}: " . $e->getMessage());
                    }
                }

                $this->repository->updateProjectSync($idProyecto, WPDM_Database::SYNC_STATUS_ACTIVE, null, count($matched));
            }
        }

        return $result;
    }

    /**
     * @param array{post_id:int, title:string, id_macroproject:string, id_proyectos:int[]} $project
     * @return array{macro_id:?int, name:string, wp_post_id:int}
     */
    private function contextFor(array $project, int $idProyecto): array
    {
        return [
            'macro_id'   => $project['id_macroproject'] !== '' ? (int) $project['id_macroproject'] : null,
            'name'       => $project['title'] . ' — ID ' . $idProyecto,
            'wp_post_id' => $project['post_id'],
        ];
    }
}
