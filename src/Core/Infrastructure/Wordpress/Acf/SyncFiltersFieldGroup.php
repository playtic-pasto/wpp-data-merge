<?php

//TODO: AQUI CREA LA OPTIONS PAGE Y LOS FILTROS DE SINCRONIZACIÓN

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Acf;

/**
 * Registra la Options Page del plugin y los campos de filtros de sincronización.
 *
 * La Options Page aparece como submenú de "WPP Data Merge" en el admin.
 * Contiene checkboxes donde el usuario selecciona qué estados y tipos
 * de unidad se incluyen al calcular las estadísticas de sincronización.
 *
 * Las opciones de los checkboxes son dinámicas:
 *  - Tipos de unidad → se cargan desde la API (/TipoUnidad) la primera vez.
 *  - Estados         → se descubren automáticamente al sincronizar proyectos.
 *
 * @see SyncCatalog     Gestiona el catálogo dinámico de estados y tipos.
 * @see SyncFiltersReader Lee los valores seleccionados para aplicar filtros.
 */
class SyncFiltersFieldGroup
{
    public const MENU_SLUG = 'wpdm-sync-filters';

    private SyncCatalog $catalog;

    public function __construct(SyncCatalog $catalog)
    {
        $this->catalog = $catalog;
    }

    /**
     * Hook en 'acf/init' para registrar la Options Page y los campos.
     * Hook en 'acf/load_field' para inyectar las choices dinámicas.
     */
    public function register(): void
    {
        add_action('acf/init', [$this, 'addOptionsPage']);
        add_action('acf/init', [$this, 'addFieldGroup']);
        add_action('acf/init', [$this, 'loadCatalogOnFirstVisit']);

        add_filter('acf/load_field/key=field_wpdm_filter_statuses', [$this, 'loadStatusChoices']);
        add_filter('acf/load_field/key=field_wpdm_filter_types', [$this, 'loadTypeChoices']);
    }

    /**
     * Crea la Options Page como submenú del plugin.
     */
    public function addOptionsPage(): void
    {
        if (!function_exists('acf_add_options_sub_page')) {
            return;
        }

        acf_add_options_sub_page([
            'page_title'  => 'Filtros',
            'menu_title'  => 'Filtros',
            'menu_slug'   => self::MENU_SLUG,
            'parent_slug' => 'wpdm-dashboard',
            'capability'  => 'manage_options',
        ]);
    }

    /**
     * Define los campos de filtros y los asigna a la Options Page.
     *
     * Los choices se dejan vacíos aquí porque se inyectan dinámicamente
     * en loadStatusChoices() y loadTypeChoices() vía el filtro acf/load_field.
     */
    public function addFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key'      => 'group_wpdm_sync_filters',
            'title'    => 'Filtros de Sincronización',
            'location' => [
                [
                    [
                        'param'    => 'options_page',
                        'operator' => '==',
                        'value'    => self::MENU_SLUG,
                    ],
                ],
            ],
            'position'     => 'normal',
            'style'        => 'default',
            'menu_order'   => 0,
            'instructions' => 'Selecciona qué estados y tipos de unidad se incluyen al sincronizar. Solo las unidades que coincidan con los valores seleccionados serán procesadas.',
            'fields'       => [
                [
                    'key'           => 'field_wpdm_filter_statuses',
                    'label'         => 'Estados a incluir',
                    'name'          => 'wpdm_filter_statuses',
                    'type'          => 'checkbox',
                    'instructions'  => 'Marca los estados de unidad que deseas incluir en la sincronización. Si no marcas ninguno, se incluirán todos. Los estados se descubren automáticamente al sincronizar.',
                    'choices'       => [],
                    'layout'        => 'horizontal',
                    'return_format' => 'value',
                ],
                [
                    'key'           => 'field_wpdm_filter_types',
                    'label'         => 'Tipos de unidad a incluir',
                    'name'          => 'wpdm_filter_types',
                    'type'          => 'checkbox',
                    'instructions'  => 'Marca los tipos de unidad que deseas incluir en la sincronización. Si no marcas ninguno, se incluirán todos. Los tipos se cargan desde la API de SINCO.',
                    'choices'       => [],
                    'layout'        => 'horizontal',
                    'return_format' => 'value',
                ],
            ],
        ]);
    }

    /**
     * La primera vez que se carga el admin, consulta la API para obtener
     * los tipos de unidad si aún no se han cargado.
     */
    public function loadCatalogOnFirstVisit(): void
    {
        if (!$this->catalog->hasTypes()) {
            $this->catalog->fetchTypesFromApi();
        }
    }

    /**
     * Inyecta las choices de estados dinámicamente desde el catálogo.
     *
     * ACF llama este filtro cada vez que renderiza el campo.
     * Las choices vienen de wp_options, alimentadas por discoverFromUnits().
     *
     * @param array<string, mixed> $field Definición del campo ACF.
     * @return array<string, mixed>
     */
    public function loadStatusChoices(array $field): array
    {
        $statuses = $this->catalog->getStatuses();

        if (!empty($statuses)) {
            $field['choices'] = $statuses;
        } else {
            $field['choices'] = [];
            $field['instructions'] .= ' ⚠️ Aún no se han descubierto estados. Sincroniza al menos un proyecto para que aparezcan aquí.';
        }

        return $field;
    }

    /**
     * Inyecta las choices de tipos de unidad dinámicamente desde el catálogo.
     *
     * @param array<string, mixed> $field Definición del campo ACF.
     * @return array<string, mixed>
     */
    public function loadTypeChoices(array $field): array
    {
        $types = $this->catalog->getTypes();

        if (!empty($types)) {
            $field['choices'] = $types;
        } else {
            $field['choices'] = [];
            $field['instructions'] .= ' ⚠️ No se pudieron cargar los tipos desde la API. Verifica la conexión.';
        }

        return $field;
    }
}
