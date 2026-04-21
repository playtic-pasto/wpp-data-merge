<?php

//TODO: AQUI CREA LOS CAMPOS ACF DE SINCRONIZACIÓN AUTOMATICAMENTE

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Acf;

use WPDM\Core\Domain\Projects\ProjectsRepository;

/**
 * Registra los campos ACF donde se guardan los datos de sincronización.
 *
 * Estos campos se llenan automáticamente al sincronizar un proyecto con SINCO.
 * No son editables por el usuario — solo se actualizan desde el plugin.
 *
 * Campos registrados:
 *  - wpdm_units_total      (Number)   → Total de unidades.
 *  - wpdm_price_min        (Number)   → Precio mínimo.
 *  - wpdm_price_max        (Number)   → Precio máximo.
 *  - wpdm_price_avg        (Number)   → Precio promedio.
 *  - wpdm_price_total      (Number)   → Suma total de precios.
 *  - wpdm_area_private_min (Number)   → Área privada mínima.
 *  - wpdm_area_private_max (Number)   → Área privada máxima.
 *  - wpdm_area_private_avg (Number)   → Área privada promedio.
 *  - wpdm_area_built_min   (Number)   → Área construida mínima.
 *  - wpdm_area_built_max   (Number)   → Área construida máxima.
 *  - wpdm_by_status        (Repeater) → Conteo de unidades por estado.
 *  - wpdm_by_type          (Repeater) → Conteo de unidades por tipo.
 *
 * @see ProjectSyncService::syncPost()       Escribe estos campos al sincronizar.
 * @see ProjectSyncService::computeStats()   Calcula los valores que se guardan aquí.
 */
class SyncDataFieldGroup
{
    /**
     * Hook en 'acf/init' para registrar los campos.
     */
    public function register(): void
    {
        add_action('acf/init', [$this, 'addFieldGroup']);
    }

    /**
     * Define el grupo de campos de sincronización y lo registra en ACF.
     */
    public function addFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key'      => 'group_wpdm_sync_data',
            'title'    => 'SINCO — Datos Sincronizados',
            'location' => [
                [
                    [
                        'param'    => 'post_type',
                        'operator' => '==',
                        'value'    => ProjectsRepository::POST_TYPE,
                    ],
                ],
            ],
            'position'     => 'normal',
            'style'        => 'default',
            'menu_order'   => 10,
            'instructions' => 'Datos calculados automáticamente al sincronizar con SINCO. No editar manualmente.',
            'fields'       => [
                ...$this->priceFields(),
                ...$this->areaFields(),
                $this->unitsTotalField(),
                $this->byStatusRepeater(),
                $this->byTypeRepeater(),
            ],
        ]);
    }

    // -------------------------------------------------------------------------
    //  Precios
    // -------------------------------------------------------------------------

    /**
     * Campos de precios: mínimo, máximo, promedio y total.
     *
     * @return array<int, array<string, mixed>>
     */
    private function priceFields(): array
    {
        return [
            [
                'key'          => 'field_wpdm_price_min',
                'label'        => 'Precio Mínimo',
                'name'         => 'wpdm_price_min',
                'type'         => 'number',
                'instructions' => 'Precio más bajo entre todas las unidades.',
                'readonly'     => 1,
            ],
            [
                'key'          => 'field_wpdm_price_max',
                'label'        => 'Precio Máximo',
                'name'         => 'wpdm_price_max',
                'type'         => 'number',
                'instructions' => 'Precio más alto entre todas las unidades.',
                'readonly'     => 1,
            ],
            [
                'key'          => 'field_wpdm_price_avg',
                'label'        => 'Precio Promedio',
                'name'         => 'wpdm_price_avg',
                'type'         => 'number',
                'instructions' => 'Promedio de precios de todas las unidades.',
                'readonly'     => 1,
            ],
            [
                'key'          => 'field_wpdm_price_total',
                'label'        => 'Precio Total',
                'name'         => 'wpdm_price_total',
                'type'         => 'number',
                'instructions' => 'Suma de precios de todas las unidades.',
                'readonly'     => 1,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    //  Áreas
    // -------------------------------------------------------------------------

    /**
     * Campos de áreas: privada (min, max, avg) y construida (min, max).
     *
     * @return array<int, array<string, mixed>>
     */
    private function areaFields(): array
    {
        return [
            [
                'key'          => 'field_wpdm_area_private_min',
                'label'        => 'Área Privada Mínima',
                'name'         => 'wpdm_area_private_min',
                'type'         => 'number',
                'instructions' => 'Área privada más pequeña (m²).',
                'readonly'     => 1,
            ],
            [
                'key'          => 'field_wpdm_area_private_max',
                'label'        => 'Área Privada Máxima',
                'name'         => 'wpdm_area_private_max',
                'type'         => 'number',
                'instructions' => 'Área privada más grande (m²).',
                'readonly'     => 1,
            ],
            [
                'key'          => 'field_wpdm_area_private_avg',
                'label'        => 'Área Privada Promedio',
                'name'         => 'wpdm_area_private_avg',
                'type'         => 'number',
                'instructions' => 'Promedio de áreas privadas (m²).',
                'readonly'     => 1,
            ],
            [
                'key'          => 'field_wpdm_area_built_min',
                'label'        => 'Área Construida Mínima',
                'name'         => 'wpdm_area_built_min',
                'type'         => 'number',
                'instructions' => 'Área construida más pequeña (m²).',
                'readonly'     => 1,
            ],
            [
                'key'          => 'field_wpdm_area_built_max',
                'label'        => 'Área Construida Máxima',
                'name'         => 'wpdm_area_built_max',
                'type'         => 'number',
                'instructions' => 'Área construida más grande (m²).',
                'readonly'     => 1,
            ],
        ];
    }

    // -------------------------------------------------------------------------
    //  Total de unidades
    // -------------------------------------------------------------------------

    /**
     * Campo: cantidad total de unidades en el proyecto.
     *
     * @return array<string, mixed>
     */
    private function unitsTotalField(): array
    {
        return [
            'key'          => 'field_wpdm_units_total',
            'label'        => 'Total de Unidades',
            'name'         => 'wpdm_units_total',
            'type'         => 'number',
            'instructions' => 'Cantidad total de unidades encontradas al sincronizar.',
            'readonly'     => 1,
        ];
    }

    // -------------------------------------------------------------------------
    //  Conteos dinámicos (repeaters)
    // -------------------------------------------------------------------------

    /**
     * Repeater: conteo de unidades agrupadas por estado (Disponible, Vendido, etc.).
     *
     * Cada fila tiene:
     *  - status_name  → Nombre del estado (ej: "Disponible").
     *  - status_count → Cantidad de unidades con ese estado.
     *
     * @return array<string, mixed>
     */
    private function byStatusRepeater(): array
    {
        return [
            'key'          => 'field_wpdm_by_status',
            'label'        => 'Unidades por Estado',
            'name'         => 'wpdm_by_status',
            'type'         => 'repeater',
            'instructions' => 'Conteo de unidades agrupadas por estado. Se actualiza al sincronizar.',
            'layout'       => 'table',
            'button_label' => '',
            'sub_fields'   => [
                [
                    'key'      => 'field_wpdm_status_name',
                    'label'    => 'Estado',
                    'name'     => 'status_name',
                    'type'     => 'text',
                    'readonly' => 1,
                ],
                [
                    'key'      => 'field_wpdm_status_count',
                    'label'    => 'Cantidad',
                    'name'     => 'status_count',
                    'type'     => 'number',
                    'readonly' => 1,
                ],
            ],
        ];
    }

    /**
     * Repeater: conteo de unidades agrupadas por tipo (Apartamento, Casa, etc.).
     *
     * Cada fila tiene:
     *  - type_name  → Nombre del tipo (ej: "Apartamento").
     *  - type_count → Cantidad de unidades de ese tipo.
     *
     * @return array<string, mixed>
     */
    private function byTypeRepeater(): array
    {
        return [
            'key'          => 'field_wpdm_by_type',
            'label'        => 'Unidades por Tipo',
            'name'         => 'wpdm_by_type',
            'type'         => 'repeater',
            'instructions' => 'Conteo de unidades agrupadas por tipo. Se actualiza al sincronizar.',
            'layout'       => 'table',
            'button_label' => '',
            'sub_fields'   => [
                [
                    'key'      => 'field_wpdm_type_name',
                    'label'    => 'Tipo',
                    'name'     => 'type_name',
                    'type'     => 'text',
                    'readonly' => 1,
                ],
                [
                    'key'      => 'field_wpdm_type_count',
                    'label'    => 'Cantidad',
                    'name'     => 'type_count',
                    'type'     => 'number',
                    'readonly' => 1,
                ],
            ],
        ];
    }
}
