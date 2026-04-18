<?php

//TODO: AQUI ES PARA OBTENER POST DE ID MACROPROYECTO Y LOS ID DE PROYECTOS ASOCIADOS

declare(strict_types=1);

namespace WPDM\Core\Domain\Projects;

/**
 * Lee los proyectos del CPT "proyecto" con sus campos ACF.
 *
 * Campos leídos:
 *  - id_macroproject  → ID del macroproyecto en SINCO.
 *  - ids_project      → Repeater ACF con sub-campo id_project.
 *
 * @see ProjectFieldGroup  Define y registra estos campos en ACF.
 * @see ProjectSyncService Consume los IDs para sincronizar con SINCO.
 */
class ProjectsRepository
{
    public const POST_TYPE = 'proyecto';

    /**
     * @return array<int, array{post_id:int, title:string, id_macroproject:int, id_proyectos:int[]}>
     */
    public function all(): array
    {
        $posts = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);

        return array_map([$this, 'mapPost'], $posts);
    }

    /**
     * @return array{post_id:int, title:string, id_macroproject:int, id_proyectos:int[]}|null
     */
    public function find(int $postId): ?array
    {
        $post = get_post($postId);
        if (!$post || $post->post_type !== self::POST_TYPE) {
            return null;
        }
        return $this->mapPost($post);
    }

    /**
     * Transforma un WP_Post en el array que usa el dominio.
     *
     * Lee el repeater ACF 'ids_project' y extrae cada sub-campo 'id_project'.
     * Si ACF no está activo, intenta leer desde post_meta directamente.
     *
     * @return array{post_id:int, title:string, id_macroproject:int, id_proyectos:int[]}
     */
    private function mapPost(\WP_Post $post): array
    {
        $idMacro = (int) $this->getField('id_macroproject', $post->ID);

        $ids = $this->getProjectIdsFromRepeater($post->ID);

        return [
            'post_id' => (int) $post->ID,
            'title' => get_the_title($post) ?: '(sin título)',
            'id_macroproject' => $idMacro,
            'id_proyectos' => $ids,
        ];
    }

    /**
     * Extrae los IDs de proyecto desde el repeater ACF 'ids_project'.
     *
     * Cada fila del repeater tiene un sub-campo 'id_project' con el ID
     * del proyecto en SINCO.
     *
     * @return int[]
     */
    private function getProjectIdsFromRepeater(int $postId): array
    {
        $rows = $this->getField('ids_project', $postId);

        if (!\is_array($rows)) {
            return [];
        }

        $ids = [];
        foreach ($rows as $row) {
            if (isset($row['id_project']) && \is_numeric($row['id_project'])) {
                $ids[] = (int) $row['id_project'];
            }
        }

        return $ids;
    }

    /**
     * Lee un campo usando ACF si está disponible, o post_meta como fallback.
     */
    private function getField(string $fieldName, int $postId): mixed
    {
        if (\function_exists('get_field')) {
            return get_field($fieldName, $postId);
        }

        return get_post_meta($postId, $fieldName, true);
    }
}
