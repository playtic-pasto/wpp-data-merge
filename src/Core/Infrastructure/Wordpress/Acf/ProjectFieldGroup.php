<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Acf;

use WPDM\Core\Domain\Projects\ProjectsRepository;

/**
 * Registra el grupo de campos ACF para el CPT "proyecto".
 *
 * Campos registrados:
 *  - id_macroproject  (Number)   → ID del macroproyecto en SINCO.
 *  - ids_project      (Repeater) → Lista de proyectos asociados.
 *    └── id_project   (Number)   → ID de un proyecto individual en SINCO.
 *  - wpdm_project_filter_statuses (Checkbox) → Estados a incluir (solo si filtros globales desactivados).
 *  - wpdm_project_filter_types    (Checkbox) → Tipos a incluir (solo si filtros globales desactivados).
 *
 * Estos campos se registran como "local" en ACF: aparecen visibles en la UI
 * pero no pueden ser editados ni eliminados desde la interfaz de ACF.
 *
 * @see ProjectsRepository::mapPost()  Lee estos campos al construir el modelo.
 * @see ProjectSyncService::syncPost() Usa los IDs para consultar la API SINCO.
 * @see SyncCatalog Provee las choices dinámicas para los filtros de estado y tipo.
 */
class ProjectFieldGroup
{
    private SyncCatalog $catalog;

    public function __construct(SyncCatalog $catalog)
    {
        $this->catalog = $catalog;
    }

    /**
     * Hook en 'acf/init' para registrar los campos.
     * Hook en 'acf/load_field' para inyectar las choices dinámicas.
     * Hook en 'acf/prepare_field' para controlar visibilidad según filtros globales.
     */
    public function register(): void
    {
        add_action('acf/init', [$this, 'addFieldGroup']);
        add_filter('acf/load_field/key=field_wpdm_project_filter_statuses', [$this, 'loadStatusChoices']);
        add_filter('acf/load_field/key=field_wpdm_project_filter_types', [$this, 'loadTypeChoices']);
        add_filter('acf/prepare_field/key=field_wpdm_project_filter_statuses', [$this, 'toggleFieldVisibility']);
        add_filter('acf/prepare_field/key=field_wpdm_project_filter_types', [$this, 'toggleFieldVisibility']);
        add_filter('acf/prepare_field/key=field_wpdm_global_filters_notice', [$this, 'toggleGlobalFiltersMessage']);
    }

    /**
     * Define el grupo de campos y lo registra en ACF.
     *
     * Se ejecuta en el hook 'acf/init', momento en que ACF ya está listo
     * para recibir definiciones de campos locales.
     */
    public function addFieldGroup(): void
    {
        if (!function_exists('acf_add_local_field_group')) {
            return;
        }

        acf_add_local_field_group([
            'key' => 'group_wpdm_project',
            'title' => 'SINCO — Configuración del Proyecto',
            'location' => [
                [
                    [
                        'param' => 'post_type',
                        'operator' => '==',
                        'value' => ProjectsRepository::POST_TYPE,
                    ],
                ],
            ],
            'position' => 'normal',
            'style' => 'default',
            'menu_order' => 0,
            'instructions' => 'Campos gestionados por el plugin WPP Data Merge. Conectan este proyecto con el ERP SINCO.',
            'fields' => [
                $this->globalFiltersNoticeField(),
                $this->macroProjectField(),
                $this->projectsRepeaterField(),
                $this->projectFilterStatusesField(),
                $this->projectFilterTypesField(),
            ],
        ]);
    }

    /**
     * Campo: ID del macroproyecto en SINCO.
     *
     * @return array<string, mixed>
     */
    private function macroProjectField(): array
    {
        return [
            'key' => 'field_wpdm_id_macroproject',
            'label' => 'ID Macroproyecto',
            'name' => 'id_macroproject',
            'type' => 'number',
            'instructions' => 'Identificador del macroproyecto en el sistema SINCO.',
            'required' => 1,
            'min' => 1,
            'step' => 1,
        ];
    }

    /**
     * Campo: Repeater con los IDs de proyectos asociados al macroproyecto.
     *
     * Cada fila del repeater contiene un único sub-campo `id_project`
     * que representa un proyecto individual dentro de SINCO.
     *
     * @return array<string, mixed>
     */
    private function projectsRepeaterField(): array
    {
        return [
            'key' => 'field_wpdm_ids_project',
            'label' => 'Proyectos Asociados',
            'name' => 'ids_project',
            'type' => 'repeater',
            'instructions' => 'Agrega los IDs de los proyectos SINCO que pertenecen a este macroproyecto.',
            'required' => 1,
            'min' => 1,
            'layout' => 'table',
            'button_label' => 'Agregar Proyecto',
            'sub_fields' => [
                [
                    'key' => 'field_wpdm_id_project',
                    'label' => 'ID Proyecto',
                    'name' => 'id_project',
                    'type' => 'number',
                    'instructions' => 'ID del proyecto en SINCO.',
                    'required' => 1,
                    'min' => 1,
                    'step' => 1,
                ],
            ],
        ];
    }

    /**
     * Campo: Mensaje informativo sobre filtros globales.
     *
     * Solo visible cuando los filtros globales están activados.
     * Informa al usuario que este proyecto usa los filtros configurados globalmente.
     *
     * @return array<string, mixed>
     */
    private function globalFiltersNoticeField(): array
    {
        $filtersUrl = admin_url('admin.php?page=wpdm-sync-filters');
        
        return [
            'key'      => 'field_wpdm_global_filters_notice',
            'label'    => '',
            'name'     => '',
            'type'     => 'message',
            'message'  => '<div class="wpdm-global-filters-notice">' .
                          '<p><strong>Filtros de Sincronización:</strong> Este proyecto está usando los <strong>Filtros Globales</strong> configurados en <a href="' . esc_url($filtersUrl) . '" target="_blank">WPP Data Merge › Filtros</a>.</p>' .
                          '<p>Si deseas configurar filtros específicos para este proyecto, desactiva la opción "Habilitar Filtros Globales" en la página de Filtros.</p>' .
                          '</div>',
            'esc_html' => 0,
        ];
    }

    /**
     * Campo: Estados a incluir en la sincronización de este proyecto.
     *
     * Solo visible cuando los filtros globales están desactivados.
     * Las choices se cargan dinámicamente desde SyncCatalog.
     *
     * @return array<string, mixed>
     */
    private function projectFilterStatusesField(): array
    {
        return [
            'key'           => 'field_wpdm_project_filter_statuses',
            'label'         => 'Estados a incluir (específicos del proyecto)',
            'name'          => 'wpdm_project_filter_statuses',
            'type'          => 'checkbox',
            'instructions'  => 'Marca los estados de unidad que deseas incluir en la sincronización de este proyecto. Si no marcas ninguno, se incluirán todos.<br><strong>Nota:</strong> Este filtro solo se aplica cuando los Filtros Globales están desactivados.',
            'choices'       => [],
            'layout'        => 'horizontal',
            'return_format' => 'value',
        ];
    }

    /**
     * Campo: Tipos de unidad a incluir en la sincronización de este proyecto.
     *
     * Solo visible cuando los filtros globales están desactivados.
     * Las choices se cargan dinámicamente desde SyncCatalog.
     *
     * @return array<string, mixed>
     */
    private function projectFilterTypesField(): array
    {
        return [
            'key'           => 'field_wpdm_project_filter_types',
            'label'         => 'Tipos de unidad a incluir (específicos del proyecto)',
            'name'          => 'wpdm_project_filter_types',
            'type'          => 'checkbox',
            'instructions'  => 'Marca los tipos de unidad que deseas incluir en la sincronización de este proyecto. Si no marcas ninguno, se incluirán todos.<br><strong>Nota:</strong> Este filtro solo se aplica cuando los Filtros Globales están desactivados.',
            'choices'       => [],
            'layout'        => 'horizontal',
            'return_format' => 'value',
        ];
    }

    /**
     * Inyecta las choices de estados dinámicamente desde el catálogo.
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
            $field['instructions'] .= '<br>⚠️ Aún no se han descubierto estados. Sincroniza al menos un proyecto para que aparezcan aquí.';
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
            $field['instructions'] .= '<br>⚠️ No se pudieron cargar los tipos desde la API. Verifica la conexión.';
        }

        return $field;
    }

    /**
     * Controla la visibilidad del mensaje de filtros globales.
     *
     * Si los filtros globales están activados, muestra este mensaje.
     * Si están desactivados, lo oculta.
     *
     * @param array<string, mixed>|false $field Definición del campo ACF.
     * @return array<string, mixed>|false
     */
    public function toggleGlobalFiltersMessage($field)
    {
        if (!$field) {
            return $field;
        }

        // Verificar si los filtros globales están activados
        $globalEnabled = false;
        if (function_exists('get_field')) {
            $globalEnabled = (bool) get_field('wpdm_enable_global_filters', 'option');
        }

        // Mostrar el mensaje solo si los filtros globales están activados
        if (!$globalEnabled) {
            return false;
        }

        return $field;
    }

    /**
     * Controla la visibilidad de los campos de filtros por proyecto.
     *
     * Si los filtros globales están activados, oculta estos campos.
     * Si están desactivados, los muestra.
     *
     * @param array<string, mixed>|false $field Definición del campo ACF.
     * @return array<string, mixed>|false
     */
    public function toggleFieldVisibility($field)
    {
        if (!$field) {
            return $field;
        }

        // Verificar si los filtros globales están activados
        $globalEnabled = false;
        if (function_exists('get_field')) {
            $globalEnabled = (bool) get_field('wpdm_enable_global_filters', 'option');
        }

        // Si los filtros globales están activados, ocultar este campo
        if ($globalEnabled) {
            return false;
        }

        return $field;
    }
}
