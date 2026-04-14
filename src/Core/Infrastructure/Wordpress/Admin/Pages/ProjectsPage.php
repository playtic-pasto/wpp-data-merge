<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Pages;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\SyncRepository;
use WPDM\Core\Domain\Sync\SyncService;
use WPDM\Core\Infrastructure\Api\SincoApiClient;
use WPDM\Core\Infrastructure\WordPress\Admin\Tables\UnitsListTable;

/**
 * Renderiza la página de proyectos con la tabla nativa de WordPress (WP_List_Table),
 * gestionando acciones por fila y en lote para sincronizar unidades.
 *
 * @name ProjectsPage
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Pages
 * @since 1.0.0
 */
class ProjectsPage
{
    private const CACHE_TTL = 300;
    private const CACHE_PREFIX = 'wpdm_units_';
    private const NOTICE_TRANSIENT = 'wpdm_projects_notice';

    private ProjectsRepository $repository;
    private SincoApiClient $sinco;
    private SyncService $syncService;
    private SyncRepository $syncRepository;

    public function __construct(
        ?ProjectsRepository $repository = null,
        ?SincoApiClient $sinco = null,
        ?SyncService $syncService = null,
        ?SyncRepository $syncRepository = null
    ) {
        $this->repository     = $repository ?? new ProjectsRepository();
        $this->sinco          = $sinco ?? new SincoApiClient();
        $this->syncService    = $syncService ?? new SyncService();
        $this->syncRepository = $syncRepository ?? new SyncRepository();
    }

    public function render(): void
    {
        $this->handleRowAction();
        $this->handleBulkActions();

        $projects       = $this->repository->all();
        $items          = [];
        $errors         = [];
        $projectOptions = [];

        foreach ($projects as $project) {
            foreach ($project['id_proyectos'] as $idProyecto) {
                $projectOptions[] = [
                    'id'    => $idProyecto,
                    'label' => sprintf('%s — ID %d', $project['title'], $idProyecto),
                ];
                $units = $this->fetchUnits($idProyecto);

                if (is_wp_error($units)) {
                    $errors[] = sprintf(
                        'Proyecto %s (macro %s, id %d): %s',
                        $project['title'],
                        $project['id_macroproject'],
                        $idProyecto,
                        $units->get_error_message()
                    );
                    continue;
                }

                foreach ($units as $unit) {
                    $unit['proyecto']        = $project['title'];
                    $unit['id_macroproject'] = $project['id_macroproject'];
                    $unit['id_proyecto_wp']  = $project['post_id'];
                    $items[] = $unit;
                }
            }
        }

        $statusMap = $this->syncRepository->getStatusMap(array_map(
            static fn($u) => (int) ($u['id'] ?? 0),
            $items
        ));

        foreach ($items as &$unit) {
            $uid  = (int) ($unit['id'] ?? 0);
            $info = $statusMap[$uid] ?? null;
            $unit['_sync_status'] = $info['sync_status'] ?? '';
            $unit['_synced_at']   = $info['synced_at']   ?? null;
            $unit['_last_error']  = $info['last_error']  ?? null;
        }
        unset($unit);

        $table = new UnitsListTable($items, $projectOptions);
        $table->prepare_items();

        $notice = get_transient(self::NOTICE_TRANSIENT);
        if ($notice) {
            delete_transient(self::NOTICE_TRANSIENT);
        }

        include WPDM_PATH . 'templates/admin/projects.php';
    }

    /**
     * @return array<int, array<string, mixed>>|\WP_Error
     */
    private function fetchUnits(int $idProyecto): array|\WP_Error
    {
        $cacheKey = self::CACHE_PREFIX . $idProyecto;
        $cached = get_transient($cacheKey);
        if (is_array($cached)) {
            return $cached;
        }

        $units = $this->sinco->getUnidadesByProyecto($idProyecto);
        if (is_wp_error($units)) {
            return $units;
        }

        set_transient($cacheKey, $units, self::CACHE_TTL);
        return $units;
    }

    private function handleRowAction(): void
    {
        if (!current_user_can('manage_options')) return;
        if (($_GET['action'] ?? '') !== 'sync_unit') return;

        $unitId = (int) ($_GET['unit'] ?? 0);
        if ($unitId <= 0) return;

        check_admin_referer('wpdm_sync_unit_' . $unitId);

        $result = $this->syncService->syncUnits([$unitId]);
        $this->storeNotice($result, 1);
        $this->redirectSelf();
    }

    private function handleBulkActions(): void
    {
        if (!current_user_can('manage_options')) return;

        $action = $_REQUEST['action'] ?? '';
        if ($action === '-1' || $action === '') {
            $action = $_REQUEST['action2'] ?? '';
        }

        if (!in_array($action, ['sync', 'refresh_cache'], true)) {
            return;
        }

        check_admin_referer('bulk-unidades');

        if ($action === 'refresh_cache') {
            foreach ($this->repository->all() as $project) {
                foreach ($project['id_proyectos'] as $idProyecto) {
                    delete_transient(self::CACHE_PREFIX . $idProyecto);
                }
            }
            set_transient(self::NOTICE_TRANSIENT, ['type' => 'success', 'message' => 'Caché SINCO refrescada.'], 30);
            $this->redirectSelf();
            return;
        }

        $ids = array_map('intval', (array) ($_REQUEST['unit'] ?? []));
        $ids = array_values(array_filter($ids, static fn($v) => $v > 0));

        if (empty($ids)) {
            set_transient(self::NOTICE_TRANSIENT, ['type' => 'warning', 'message' => 'Selecciona al menos una unidad para sincronizar.'], 30);
            $this->redirectSelf();
            return;
        }

        $result = $this->syncService->syncUnits($ids);
        $this->storeNotice($result, count($ids));
        $this->redirectSelf();
    }

    private function storeNotice(array $result, int $requested): void
    {
        $synced = (int) $result['synced'];
        $failed = (int) $result['failed'];
        $errs   = $result['errors'] ?? [];

        if ($synced > 0 && $failed === 0 && empty($errs)) {
            $type = 'success';
            $msg  = sprintf('%d unidad(es) sincronizada(s) correctamente.', $synced);
        } elseif ($synced === 0) {
            $type = 'error';
            $msg  = 'No se sincronizó ninguna unidad. ' . implode(' | ', $errs);
        } else {
            $type = 'warning';
            $msg  = sprintf('%d sincronizada(s), %d con error. %s', $synced, $failed, implode(' | ', $errs));
        }

        set_transient(self::NOTICE_TRANSIENT, ['type' => $type, 'message' => $msg], 30);
    }

    private function redirectSelf(): void
    {
        $url = remove_query_arg(['action', 'action2', 'unit', '_wpnonce', '_wp_http_referer']);
        if (empty($url)) {
            $url = admin_url('admin.php?page=wpdm-projects');
        }
        wp_safe_redirect($url);
        exit;
    }
}
