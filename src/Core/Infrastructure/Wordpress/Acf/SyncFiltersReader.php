<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Acf;

/**
 * Lee los filtros de sincronización guardados en la Options Page de ACF.
 *
 * Proporciona los valores seleccionados por el usuario para estados y tipos
 * de unidad. El ProjectSyncService usa esta clase para filtrar las unidades
 * antes de calcular las estadísticas.
 *
 * @see SyncFiltersFieldGroup  Define los campos que esta clase lee.
 * @see ProjectSyncService     Consume los filtros al sincronizar.
 */
class SyncFiltersReader
{
    /**
     * Obtiene los estados seleccionados para incluir en la sincronización.
     *
     * @return string[] Lista de estados (ej: ['Disponible', 'Opcionado']). Vacío = todos.
     */
    public function getSelectedStatuses(): array
    {
        return $this->getOptionField('wpdm_filter_statuses');
    }

    /**
     * Obtiene los tipos de unidad seleccionados para incluir en la sincronización.
     *
     * @return string[] Lista de tipos (ej: ['Apartamento', 'Parqueadero']). Vacío = todos.
     */
    public function getSelectedTypes(): array
    {
        return $this->getOptionField('wpdm_filter_types');
    }

    /**
     * Filtra un conjunto de unidades según los filtros configurados.
     *
     * Solo conserva las unidades cuyo 'estado' esté en los estados seleccionados
     * Y cuyo 'tipoUnidad' esté en los tipos seleccionados.
     * Si no hay filtros seleccionados para una categoría, no se filtra por ella.
     *
     * @param array<int, array<string, mixed>> $units Unidades crudas de la API SINCO.
     * @return array<int, array<string, mixed>> Unidades que pasan los filtros.
     */
    public function applyFilters(array $units): array
    {
        $statuses = $this->getSelectedStatuses();
        $types = $this->getSelectedTypes();

        $hasStatusFilter = !empty($statuses);
        $hasTypeFilter = !empty($types);

        if (!$hasStatusFilter && !$hasTypeFilter) {
            return $units;
        }

        return array_values(array_filter($units, function (array $unit) use ($statuses, $types, $hasStatusFilter, $hasTypeFilter): bool {
            if ($hasStatusFilter) {
                $unitStatus = (string) ($unit['estado'] ?? '');
                if (!in_array($unitStatus, $statuses, true)) {
                    return false;
                }
            }

            if ($hasTypeFilter) {
                $unitType = (string) ($unit['tipoUnidad'] ?? '');
                if (!in_array($unitType, $types, true)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Lee un campo de la Options Page de ACF.
     *
     * @return string[]
     */
    private function getOptionField(string $fieldName): array
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $value = get_field($fieldName, 'option');

        if (!is_array($value)) {
            return [];
        }

        return $value;
    }
}
