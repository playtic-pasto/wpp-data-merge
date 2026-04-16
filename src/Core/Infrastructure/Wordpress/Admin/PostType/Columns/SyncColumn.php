<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\PostType\Columns;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\Api\CredentialLoader;
use WPDM\Core\Infrastructure\WordPress\Admin\Support\Formatter;
use WPDM\Core\Infrastructure\WordPress\Admin\Support\StatusBadge;
use WPDM\Core\Infrastructure\WordPress\Admin\Support\SyncUrlBuilder;

/**
 * Columna "Sincronización SINCO" y row action "Sincronizar" en el listado del
 * CPT proyecto.
 *
 * @name SyncColumn
 * @package WPDM\Core\Infrastructure\WordPress\Admin\PostType\Columns
 * @since 1.0.0
 */
class SyncColumn
{
    private const POST_TYPE = ProjectsRepository::POST_TYPE;
    private const COLUMN_KEY = 'wpdm_sync';

    private CredentialLoader $credentials;

    public function __construct(?CredentialLoader $credentials = null)
    {
        $this->credentials = $credentials ?? new CredentialLoader();
    }

    public function register(): void
    {
        \add_filter('manage_' . self::POST_TYPE . '_posts_columns', [$this, 'addColumn']);
        \add_action('manage_' . self::POST_TYPE . '_posts_custom_column', [$this, 'renderColumn'], 10, 2);
        \add_filter('post_row_actions', [$this, 'addRowAction'], 10, 2);
    }

    public function addColumn(array $columns): array
    {
        $new = [];
        foreach ($columns as $key => $label) {
            $new[$key] = $label;
            if ($key === 'title') {
                $new[self::COLUMN_KEY] = 'Sincronización SINCO';
            }
        }
        return $new;
    }

    public function renderColumn(string $column, int $postId): void
    {
        if ($column !== self::COLUMN_KEY)
            return;

        $status = (string) \get_post_meta($postId, ProjectSyncService::META_SYNC_STATUS, true);
        $syncedAt = (string) \get_post_meta($postId, ProjectSyncService::META_LAST_SYNCED, true);
        $agg = ProjectSyncService::readSummary($postId);

        echo StatusBadge::render($status);

        if ($syncedAt !== '') {
            echo '<div style="color:#646970;font-size:11px;margin-top:4px;">'
                . \esc_html(Formatter::dateTime($syncedAt))
                . '</div>';
        }

        if (!empty($agg['units_total'])) {
            echo '<div style="color:#646970;font-size:11px;">'
                . \esc_html(\sprintf(
                    '%d unid. · min $%s · max $%s',
                    (int) $agg['units_total'],
                    Formatter::money($agg['price_min'] ?? null),
                    Formatter::money($agg['price_max'] ?? null)
                ))
                . '</div>';
        }
    }

    public function addRowAction(array $actions, \WP_Post $post): array
    {
        if ($post->post_type !== self::POST_TYPE)
            return $actions;
        if ($this->credentials->loadCredentials() === null)
            return $actions;

        $url = SyncUrlBuilder::build($post->ID, \get_edit_post_link($post->ID, ''));
        $actions['wpdm_sync'] = \sprintf(
            '<a href="%s" class="wpdm-sync-project" data-title="%s">Sincronizar</a>',
            \esc_url($url),
            \esc_attr(\get_the_title($post))
        );
        return $actions;
    }
}
