<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Pages;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Infrastructure\Api\SincoApiClient;
use WPDM\Core\Infrastructure\WordPress\Admin\Tables\UnitsListTable;

/**
 * Renderiza la página de proyectos con la tabla nativa de WordPress (WP_List_Table)
 * listando todas las unidades obtenidas desde SINCO.
 *
 * @name ProjectsPage
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Pages
 * @since 1.0.0
 */
class ProjectsPage
{
    private const CACHE_TTL = 300;
    private const CACHE_PREFIX = 'wpdm_units_';

    private ProjectsRepository $repository;
    private SincoApiClient $sinco;

    public function __construct(
        ?ProjectsRepository $repository = null,
        ?SincoApiClient $sinco = null
    ) {
        $this->repository = $repository ?? new ProjectsRepository();
        $this->sinco      = $sinco ?? new SincoApiClient();
    }

    public function render(): void
    {
        $this->handleBulkActions();

        $projects = $this->repository->all();
        $items    = [];
        $errors   = [];

        foreach ($projects as $project) {
            foreach ($project['id_proyectos'] as $idProyecto) {
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

        $table = new UnitsListTable($items);
        $table->prepare_items();

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

    private function handleBulkActions(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $action = $_REQUEST['action'] ?? $_REQUEST['action2'] ?? '';
        if ($action !== 'refresh_cache') {
            return;
        }

        check_admin_referer('bulk-unidades');

        foreach ($this->repository->all() as $project) {
            foreach ($project['id_proyectos'] as $idProyecto) {
                delete_transient(self::CACHE_PREFIX . $idProyecto);
            }
        }
    }
}
