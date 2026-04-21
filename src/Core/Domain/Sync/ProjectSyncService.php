<?php

declare(strict_types=1);

namespace WPDM\Core\Domain\Sync;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Infrastructure\Api\SincoApiClient;
use WPDM\Core\Infrastructure\Database\WPDM_Database;
use WPDM\Core\Infrastructure\WordPress\Acf\SyncCatalog;
use WPDM\Core\Infrastructure\WordPress\Acf\SyncFiltersReader;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Orquesta la sincronización a nivel de proyecto (CPT "proyecto"):
 * consulta las unidades en SINCO, descubre estados/tipos nuevos,
 * aplica los filtros configurados y persiste los datos en campos ACF.
 *
 * @see SyncCatalog            Descubre y guarda estados/tipos nuevos.
 * @see SyncFiltersReader      Lee los filtros de estado y tipo seleccionados.
 * @see SyncDataFieldGroup     Define los campos ACF donde se guardan los datos.
 */
class ProjectSyncService
{
    /**
     * Prefijo común para todas las meta_keys del plugin en wp_postmeta.
     * El "_" inicial las marca como internas (WordPress las oculta del editor
     * visual de Custom Fields) y "wpdm_" evita colisiones con otros plugins.
     */
    public const PREFIX = '_wpdm_';

    /**
     * Estado del último intento de sincronización.
     * Valores posibles: 'active' (ok), 'pending' (pausado), 'error' (falló).
     * Se consulta desde la columna y el badge para pintar el color.
     */
    public const META_SYNC_STATUS = self::PREFIX . 'sync_status';

    /**
     * Timestamp (formato MySQL 'Y-m-d H:i:s') del último sync — exitoso o no.
     * Sirve para mostrar "Última sincronización" y detectar datos obsoletos.
     */
    public const META_LAST_SYNCED = self::PREFIX . 'last_synced_at';

    /**
     * Mensaje del último error ocurrido durante la sincronización.
     * Queda vacío cuando el último sync fue exitoso. Se muestra en la tarjeta
     * lateral cuando el estado es 'error'.
     */
    public const META_LAST_ERROR = self::PREFIX . 'last_error';

    /**
     * Resumen consolidado (JSON) de TODAS las unidades del proyecto: totales,
     * precio min/max/prom/total, áreas min/max/prom, conteo por estado y por
     * tipo, cantidad de id_projects incluidos y fecha del sync.
     */
    public const META_SUMMARY = self::PREFIX . 'summary';

    /**
     * Detalle (JSON) separado por cada id_proyecto del CSV configurado en ACF.
     * Usa el id_proyecto como clave y almacena las mismas estadísticas que
     * el resumen, pero acotadas a ese proyecto. Permite ver el aporte
     * individual de cada proyecto al total consolidado.
     */
    public const META_BY_PROJECT = self::PREFIX . 'by_project';

    private SincoApiClient $sinco;
    private ProjectsRepository $projects;
    private SyncFiltersReader $filters;
    private SyncCatalog $catalog;
    private WPDM_Logger $logger;

    public function __construct(
        ?SincoApiClient $sinco = null,
        ?ProjectsRepository $projects = null,
        ?WPDM_Logger $logger = null,
        ?SyncFiltersReader $filters = null,
        ?SyncCatalog $catalog = null
    ) {
        $this->sinco = $sinco ?? new SincoApiClient();
        $this->projects = $projects ?? new ProjectsRepository();
        $this->logger = $logger ?? new WPDM_Logger(WPDM_PATH);
        $this->filters = $filters ?? new SyncFiltersReader();
        $this->catalog = $catalog ?? new SyncCatalog();
    }

    /**
     * Sincroniza un post del CPT proyecto. Devuelve el resumen persistido.
     *
     * @return array{success:bool, message:string, aggregates?:array<string,mixed>}
     */
    public function syncPost(int $postId): array
    {
        $project = $this->projects->find($postId);
        if ($project === null) {
            return ['success' => false, 'message' => 'El post no corresponde a un proyecto válido.'];
        }
        if (empty($project['id_projects'])) {
            return ['success' => false, 'message' => 'El proyecto no tiene id_proyecto configurado.'];
        }

        $allUnits = [];
        $breakdown = [];
        $errors = [];

        foreach ($project['id_projects'] as $idProyecto) {
            $units = $this->sinco->getUnidadesByProyecto($idProyecto);

            if (\is_wp_error($units)) {
                $msg = $units->get_error_message();
                $this->logger->error("ProjectSync: proyecto {$idProyecto} falló: {$msg}");
                $errors[] = "Proyecto {$idProyecto}: {$msg}";
                $breakdown[$idProyecto] = ['error' => $msg];
                continue;
            }

            $stats = $this->computeStats($units);
            $breakdown[$idProyecto] = $stats;
            foreach ($units as $u) {
                $allUnits[] = $u;
            }
        }

        $now = current_time('mysql');

        $this->catalog->discoverFromUnits($allUnits);
        $allUnits = $this->filters->applyFilters($allUnits, $postId);

        if (empty($allUnits) && !empty($errors)) {
            update_post_meta($postId, self::META_SYNC_STATUS, WPDM_Database::SYNC_STATUS_ERROR);
            update_post_meta($postId, self::META_LAST_ERROR, \implode(' | ', $errors));
            update_post_meta($postId, self::META_LAST_SYNCED, $now);
            return ['success' => false, 'message' => 'Error al sincronizar: ' . \implode(' | ', $errors)];
        }

        $aggregates = $this->computeStats($allUnits);
        $aggregates['projects_count'] = \count($project['id_projects']);
        $aggregates['synced_at'] = $now;

        $this->saveAcfFields($postId, $aggregates);
        $this->saveSyncMeta($postId, $now, $errors, $aggregates, $breakdown);

        return [
            'success' => empty($errors),
            'message' => empty($errors)
                ? \sprintf('Sincronización completa: %d unidades en %d proyecto(s).', $aggregates['units_total'], $aggregates['projects_count'])
                : \sprintf('Sincronizado parcialmente: %d unidades. Errores: %s', $aggregates['units_total'], \implode(' | ', $errors)),
            'aggregates' => $aggregates,
        ];
    }

    /**
     * Sincroniza todos los posts del CPT proyecto que no estén pausados.
     * Este es el punto de entrada que usará el cron job.
     *
     * Un proyecto se salta si su _wpdm_sync_status es 'pending' (el usuario
     * lo pausó manualmente). Los que estén en 'error' sí se reintentan.
     *
     * @return array{processed:int, succeeded:int, failed:int, skipped:int, details:list<array{post_id:int,title:string,result:array<string,mixed>}>}
     */
    public function syncAllActive(): array
    {
        $report = ['processed' => 0, 'succeeded' => 0, 'failed' => 0, 'skipped' => 0, 'details' => []];

        foreach ($this->projects->all() as $project) {
            if (empty($project['id_projects'])) {
                $report['skipped']++;
                continue;
            }

            $status = (string) \get_post_meta($project['post_id'], self::META_SYNC_STATUS, true);
            if ($status === WPDM_Database::SYNC_STATUS_PENDING) {
                $report['skipped']++;
                continue;
            }

            $result = $this->syncPost($project['post_id']);
            $report['processed']++;
            $report[$result['success'] ? 'succeeded' : 'failed']++;
            $report['details'][] = [
                'post_id' => $project['post_id'],
                'title'   => $project['title'],
                'result'  => $result,
            ];
        }

        $this->logger->info(\sprintf(
            'SyncAll: %d procesados (%d ok, %d error, %d saltados)',
            $report['processed'],
            $report['succeeded'],
            $report['failed'],
            $report['skipped']
        ));

        return $report;
    }

    /**
     * Decodifica el resumen consolidado guardado como JSON en post_meta.
     *
     * @return array<string, mixed>
     */
    public static function readSummary(int $postId): array
    {
        return self::readJsonMeta($postId, self::META_SUMMARY);
    }

    /**
     * Decodifica el detalle por id_proyecto guardado como JSON.
     *
     * @return array<int|string, mixed>
     */
    public static function readByProject(int $postId): array
    {
        return self::readJsonMeta($postId, self::META_BY_PROJECT);
    }

    /**
     * @return array<int|string, mixed>
     */
    private static function readJsonMeta(int $postId, string $key): array
    {
        $raw = \get_post_meta($postId, $key, true);
        if (\is_array($raw))
            return $raw;
        if (!\is_string($raw) || $raw === '')
            return [];
        $decoded = \json_decode($raw, true);
        return \is_array($decoded) ? $decoded : [];
    }

    /**
     * Calcula estadísticas agregadas sobre un conjunto de unidades SINCO.
     *
     * @param array<int, array<string, mixed>> $units
     * @return array<string, mixed>
     */
    private function computeStats(array $units): array
    {
        $total = \count($units);
        $prices = [];
        $private = [];
        $built = [];
        $byStatus = [];
        $byType = [];

        foreach ($units as $u) {
            if (isset($u['valor']) && \is_numeric($u['valor']) && $u['valor'] > 0) {
                $prices[] = (float) $u['valor'];
            }
            if (isset($u['areaPrivada']) && \is_numeric($u['areaPrivada']) && $u['areaPrivada'] > 0) {
                $private[] = (float) $u['areaPrivada'];
            }
            if (isset($u['areaConstruida']) && \is_numeric($u['areaConstruida']) && $u['areaConstruida'] > 0) {
                $built[] = (float) $u['areaConstruida'];
            }
            $status = (string) ($u['estado'] ?? '—');
            $byStatus[$status] = ($byStatus[$status] ?? 0) + 1;

            $type = (string) ($u['tipoUnidad'] ?? '—');
            $byType[$type] = ($byType[$type] ?? 0) + 1;
        }

        return [
            'units_total' => $total,
            'price_min' => $prices ? \min($prices) : null,
            'price_max' => $prices ? \max($prices) : null,
            'price_avg' => $prices ? \array_sum($prices) / \count($prices) : null,
            'price_total' => $prices ? \array_sum($prices) : null,
            'private_area_min' => $private ? \min($private) : null,
            'private_area_max' => $private ? \max($private) : null,
            'private_area_avg' => $private ? \array_sum($private) / \count($private) : null,
            'built_area_min' => $built ? \min($built) : null,
            'built_area_max' => $built ? \max($built) : null,
            'by_status' => $byStatus,
            'by_type' => $byType,
        ];
    }

    /**
     * Guarda los datos calculados en campos ACF individuales.
     *
     * Cada valor numérico se escribe en su propio campo ACF para que sea
     * consultable con meta_query y visible en la UI del proyecto.
     * Los conteos por estado y tipo se guardan como repeaters ACF.
     *
     * @see SyncDataFieldGroup  Define los campos ACF que se llenan aquí.
     *
     * @param array<string, mixed> $aggregates Datos calculados por computeStats().
     */
    private function saveAcfFields(int $postId, array $aggregates): void
    {
        $numericFields = [
            'wpdm_units_total'      => 'units_total',
            'wpdm_price_min'        => 'price_min',
            'wpdm_price_max'        => 'price_max',
            'wpdm_price_avg'        => 'price_avg',
            'wpdm_price_total'      => 'price_total',
            'wpdm_area_private_min' => 'private_area_min',
            'wpdm_area_private_max' => 'private_area_max',
            'wpdm_area_private_avg' => 'private_area_avg',
            'wpdm_area_built_min'   => 'built_area_min',
            'wpdm_area_built_max'   => 'built_area_max',
        ];

        foreach ($numericFields as $acfFieldName => $aggregateKey) {
            update_field($acfFieldName, $aggregates[$aggregateKey], $postId);
        }

        $statusRows = [];
        foreach (($aggregates['by_status'] ?? []) as $name => $count) {
            $statusRows[] = ['status_name' => $name, 'status_count' => $count];
        }
        update_field('wpdm_by_status', $statusRows, $postId);

        $typeRows = [];
        foreach (($aggregates['by_type'] ?? []) as $name => $count) {
            $typeRows[] = ['type_name' => $name, 'type_count' => $count];
        }
        update_field('wpdm_by_type', $typeRows, $postId);
    }

    /**
     * Guarda los metadatos internos de control de sincronización.
     *
     * Estos campos NO son ACF — son post_meta internos del plugin
     * para controlar el estado, errores y detalle por proyecto.
     *
     * @param array<string, mixed>        $aggregates Datos consolidados.
     * @param array<int|string, mixed>    $breakdown  Detalle por id_proyecto.
     * @param list<string>                $errors     Errores ocurridos.
     */
    private function saveSyncMeta(int $postId, string $now, array $errors, array $aggregates, array $breakdown): void
    {
        update_post_meta($postId, self::META_SUMMARY, \wp_slash(\wp_json_encode($aggregates)));
        update_post_meta($postId, self::META_BY_PROJECT, \wp_slash(\wp_json_encode($breakdown)));
        update_post_meta($postId, self::META_LAST_SYNCED, $now);
        update_post_meta($postId, self::META_SYNC_STATUS, empty($errors) ? WPDM_Database::SYNC_STATUS_ACTIVE : WPDM_Database::SYNC_STATUS_ERROR);
        update_post_meta($postId, self::META_LAST_ERROR, empty($errors) ? '' : \implode(' | ', $errors));
    }
}
