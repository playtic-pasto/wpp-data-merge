<?php

declare(strict_types=1);

namespace WPDM\Core\Domain\Sync;

use WPDM\Core\Infrastructure\Database\WPDM_Database;

/**
 * Acceso a las tablas wpdm_projects y wpdm_units para operaciones de
 * sincronización (upserts, marcado de errores, conteos).
 *
 * @name SyncRepository
 * @package WPDM\Core\Domain\Sync
 * @since 1.0.0
 */
class SyncRepository
{
    private \wpdb $db;
    private string $projectsTable;
    private string $unitsTable;

    public function __construct(?\wpdb $db = null)
    {
        global $wpdb;
        $this->db            = $db ?? $wpdb;
        $this->projectsTable = $this->db->prefix . WPDM_Database::TABLE_PROJECTS;
        $this->unitsTable    = $this->db->prefix . WPDM_Database::TABLE_UNITS;
    }

    /**
     * Garantiza una fila en wpdm_projects. No pisa el sync_status ni last_error si la fila ya existe.
     */
    public function ensureProject(int $projectId, ?int $macroId, ?string $name, ?int $wpPostId): void
    {
        $existing = $this->db->get_var(
            $this->db->prepare(
                "SELECT project_id FROM {$this->projectsTable} WHERE project_id = %d",
                $projectId
            )
        );

        if ($existing) {
            $this->db->update(
                $this->projectsTable,
                array_filter([
                    'macroproject_id' => $macroId,
                    'name'            => $name,
                    'wp_post_id'      => $wpPostId,
                ], static fn($v) => $v !== null),
                ['project_id' => $projectId]
            );
            return;
        }

        $this->db->insert(
            $this->projectsTable,
            [
                'project_id'      => $projectId,
                'macroproject_id' => $macroId,
                'name'            => $name,
                'wp_post_id'      => $wpPostId,
                'sync_status'     => WPDM_Database::SYNC_STATUS_ACTIVE,
            ]
        );
    }

    /**
     * Actualiza el estado de sync de un proyecto y un contador/timestamp.
     */
    public function updateProjectSync(int $projectId, string $status, ?string $error = null, ?int $unitsCount = null): void
    {
        $data = [
            'sync_status'    => $status,
            'last_error'     => $error,
            'last_synced_at' => current_time('mysql'),
        ];
        if ($unitsCount !== null) {
            $data['units_count'] = $unitsCount;
        }

        $this->db->update($this->projectsTable, $data, ['project_id' => $projectId]);
    }

    /**
     * Upsert de una unidad. Pisa todas las columnas calientes + raw_data + synced_at.
     */
    public function upsertUnit(array $unit, int $projectId, ?int $macroId): void
    {
        $row = [
            'id'               => (int) ($unit['id'] ?? 0),
            'project_id'       => $projectId,
            'macroproject_id'  => $macroId,
            'name'             => $unit['nombre'] ?? null,
            'unit_type'        => $unit['tipoUnidad'] ?? null,
            'property_type'    => $unit['tipoInmueble'] ?? null,
            'status'           => $unit['estado'] ?? null,
            'price'            => isset($unit['valor']) ? (float) $unit['valor'] : null,
            'private_area'     => isset($unit['areaPrivada']) ? (float) $unit['areaPrivada'] : null,
            'built_area'       => isset($unit['areaConstruida']) ? (float) $unit['areaConstruida'] : null,
            'floor_number'     => isset($unit['numeroPiso']) ? (int) $unit['numeroPiso'] : null,
            'delivery_date'    => $this->normalizeDate($unit['fechaEntrega'] ?? null),
            'is_main'          => !empty($unit['esPrincipal']) ? 1 : 0,
            'is_blocked'       => !empty($unit['estBloq']) ? 1 : 0,
            'raw_data'         => wp_json_encode($unit),
            'synced_at'        => current_time('mysql'),
            'deleted_at'       => null,
        ];

        $exists = (int) $this->db->get_var(
            $this->db->prepare("SELECT id FROM {$this->unitsTable} WHERE id = %d", $row['id'])
        );

        if ($exists) {
            $this->db->update($this->unitsTable, $row, ['id' => $row['id']]);
        } else {
            $row['sync_status'] = WPDM_Database::SYNC_STATUS_ACTIVE;
            $this->db->insert($this->unitsTable, $row);
        }
    }

    /**
     * Marca una unidad con error de sincronización.
     */
    public function markUnitError(int $unitId, string $error): void
    {
        $this->db->update(
            $this->unitsTable,
            [
                'sync_status' => WPDM_Database::SYNC_STATUS_ERROR,
                'last_error'  => $error,
            ],
            ['id' => $unitId]
        );
    }

    /**
     * Devuelve un mapa id => ['sync_status' => string, 'synced_at' => string|null, 'last_error' => string|null]
     * para los ids proporcionados.
     *
     * @param int[] $ids
     * @return array<int, array{sync_status:string, synced_at:?string, last_error:?string}>
     */
    public function getStatusMap(array $ids): array
    {
        if (empty($ids)) return [];
        $ids = array_values(array_unique(array_map('intval', $ids)));

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $sql = $this->db->prepare(
            "SELECT id, sync_status, synced_at, last_error FROM {$this->unitsTable} WHERE id IN ($placeholders)",
            ...$ids
        );
        $rows = $this->db->get_results($sql, ARRAY_A) ?: [];

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r['id']] = [
                'sync_status' => (string) $r['sync_status'],
                'synced_at'   => $r['synced_at'] ?: null,
                'last_error'  => $r['last_error'] ?: null,
            ];
        }
        return $map;
    }

    private function normalizeDate(?string $raw): ?string
    {
        if (!$raw) return null;
        $ts = strtotime($raw);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}
