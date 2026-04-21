<?php

//TODO: AQUI CREA LOS CAMPOS ACF AUTOMATICAMENTE SIN NECESIDAD DE REGISTRARLOS MANUALMENTE

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
 *
 * Estos campos se registran como "local" en ACF: aparecen visibles en la UI
 * pero no pueden ser editados ni eliminados desde la interfaz de ACF.
 *
 * @see ProjectsRepository::mapPost()  Lee estos campos al construir el modelo.
 * @see ProjectSyncService::syncPost() Usa los IDs para consultar la API SINCO.
 */
class ProjectFieldGroup
{
    /**
     * Hook en 'acf/init' para registrar los campos.
     */
    public function register(): void
    {
        add_action('acf/init', [$this, 'addFieldGroup']);
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
                $this->macroProjectField(),
                $this->projectsRepeaterField(),
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
}
