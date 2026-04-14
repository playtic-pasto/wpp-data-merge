<?php

declare(strict_types=1);

namespace WPDM\Core\Domain\Projects;

/**
 * Repositorio que lee los proyectos registrados como CPT "proyecto" junto con los
 * campos ACF id_macroproject e id_proyecto (CSV).
 *
 * @name ProjectsRepository
 * @package WPDM\Core\Domain\Projects
 * @since 1.0.0
 */
class ProjectsRepository
{
    private const POST_TYPE = 'proyecto';

    /**
     * Devuelve las entradas del CPT proyecto con sus IDs SINCO parseados.
     *
     * @return array<int, array{post_id:int, title:string, id_macroproject:string, id_proyectos:int[]}>
     */
    public function all(): array
    {
        $posts = get_posts([
            'post_type'      => self::POST_TYPE,
            'post_status'    => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
        ]);

        $items = [];
        foreach ($posts as $post) {
            $idMacro   = (string) (function_exists('get_field') ? get_field('id_macroproject', $post->ID) : get_post_meta($post->ID, 'id_macroproject', true));
            $idProyRaw = (string) (function_exists('get_field') ? get_field('id_proyecto', $post->ID) : get_post_meta($post->ID, 'id_proyecto', true));

            $ids = array_values(array_filter(array_map(
                static fn($v) => (int) trim((string) $v),
                explode(',', $idProyRaw)
            )));

            $items[] = [
                'post_id'         => (int) $post->ID,
                'title'           => get_the_title($post) ?: '(sin título)',
                'id_macroproject' => $idMacro,
                'id_proyectos'    => $ids,
            ];
        }

        return $items;
    }
}
