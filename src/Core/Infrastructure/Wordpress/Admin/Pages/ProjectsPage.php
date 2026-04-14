<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Pages;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Infrastructure\Api\SincoApiClient;

/**
 * Renderiza la página de proyectos listando las unidades obtenidas desde SINCO
 * para cada proyecto asociado al CPT "proyecto".
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
        $projects = $this->repository->all();
        $groups   = [];
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
                    $units = [];
                }

                $groups[] = [
                    'project'      => $project,
                    'id_proyecto'  => $idProyecto,
                    'units'        => $units,
                ];
            }
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
}
