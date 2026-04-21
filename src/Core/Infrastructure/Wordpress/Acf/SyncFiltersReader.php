<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Acf;

/**
 * Lee los filtros de sincronización guardados en la Options Page de ACF
 * o en los campos de cada proyecto individual.
 *
 * Proporciona los valores seleccionados por el usuario para estados y tipos
 * de unidad. El ProjectSyncService usa esta clase para filtrar las unidades
 * antes de calcular las estadísticas.
 *
 * Soporta dos modos:
 *  - Filtros globales (Options Page) → se aplican a todos los proyectos.
 *  - Filtros por proyecto → cada proyecto define sus propios filtros.
 *
 * @see SyncFiltersFieldGroup  Define los campos globales que esta clase lee.
 * @see ProjectFieldGroup      Define los campos por proyecto.
 * @see ProjectSyncService     Consume los filtros al sincronizar.
 */
class SyncFiltersReader
{
    /**
     * Verifica si los filtros globales están activados.
     *
     * @return bool true si los filtros globales están activados, false si están desactivados.
     */
    public function isGlobalEnabled(): bool
    {
        if (!function_exists('get_field')) {
            return true; // Por defecto, usar filtros globales si ACF no está disponible
        }

        $enabled = get_field('wpdm_enable_global_filters', 'option');

        // Si no está configurado, por defecto está activado (valor default del campo)
        return $enabled !== false && $enabled !== 0 && $enabled !== '0';
    }

    /**
     * Obtiene los estados seleccionados para incluir en la sincronización.
     *
     * Si los filtros globales están activados, lee de la Options Page.
     * Si están desactivados, lee del proyecto específico.
     *
     * @param int|null $postId ID del proyecto. Si es null, siempre lee de opciones globales.
     * @return string[] Lista de estados (ej: ['Disponible', 'Opcionado']). Vacío = todos.
     */
    public function getSelectedStatuses(?int $postId = null): array
    {
        if ($postId !== null && !$this->isGlobalEnabled()) {
            return $this->getPostField($postId, 'wpdm_project_filter_statuses');
        }

        return $this->getOptionField('wpdm_filter_statuses');
    }

    /**
     * Obtiene los tipos de unidad seleccionados para incluir en la sincronización.
     *
     * Si los filtros globales están activados, lee de la Options Page.
     * Si están desactivados, lee del proyecto específico.
     *
     * @param int|null $postId ID del proyecto. Si es null, siempre lee de opciones globales.
     * @return string[] Lista de tipos (ej: ['Apartamento', 'Parqueadero']). Vacío = todos.
     */
    public function getSelectedTypes(?int $postId = null): array
    {
        if ($postId !== null && !$this->isGlobalEnabled()) {
            return $this->getPostField($postId, 'wpdm_project_filter_types');
        }

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
     * @param int|null $postId ID del proyecto (para resolver si usar filtros globales o por proyecto).
     * @return array<int, array<string, mixed>> Unidades que pasan los filtros.
     */
    public function applyFilters(array $units, ?int $postId = null): array
    {
        $statuses = $this->getSelectedStatuses($postId);
        $types = $this->getSelectedTypes($postId);

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

    /**
     * Lee un campo de un post específico.
     *
     * @return string[]
     */
    private function getPostField(int $postId, string $fieldName): array
    {
        if (!function_exists('get_field')) {
            return [];
        }

        $value = get_field($fieldName, $postId);

        if (!is_array($value)) {
            return [];
        }

        return $value;
    }
}
