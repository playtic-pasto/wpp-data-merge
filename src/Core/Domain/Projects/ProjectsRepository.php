<?php

declare(strict_types=1);

namespace WPDM\Core\Domain\Projects;

/**
 * Lee los proyectos del CPT "proyecto" con sus campos ACF id_macroproject
 * (único) e id_proyecto (CSV de ids SINCO).
 *
 * @name ProjectsRepository
 * @package WPDM\Core\Domain\Projects
 * @since 1.0.0
 */
class ProjectsRepository
{
    public const POST_TYPE = 'proyecto';

    /**
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

        return array_map([$this, 'mapPost'], $posts);
    }

    /**
     * @return array{post_id:int, title:string, id_macroproject:string, id_proyectos:int[]}|null
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
     * @return array{post_id:int, title:string, id_macroproject:string, id_proyectos:int[]}
     */
    private function mapPost(\WP_Post $post): array
    {
        $idMacro   = (string) (\function_exists('get_field') ? get_field('id_macroproject', $post->ID) : get_post_meta($post->ID, 'id_macroproject', true));
        $idProyRaw = (string) (\function_exists('get_field') ? get_field('id_proyecto', $post->ID) : get_post_meta($post->ID, 'id_proyecto', true));

        $ids = array_values(array_filter(array_map(
            static fn($v) => (int) trim((string) $v),
            explode(',', $idProyRaw)
        )));

        return [
            'post_id'         => (int) $post->ID,
            'title'           => get_the_title($post) ?: '(sin título)',
            'id_macroproject' => $idMacro,
            'id_proyectos'    => $ids,
        ];
    }
}
