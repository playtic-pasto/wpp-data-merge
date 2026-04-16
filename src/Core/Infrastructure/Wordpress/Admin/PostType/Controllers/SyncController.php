<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\PostType\Controllers;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\WordPress\Admin\Support\SyncUrlBuilder;

/**
 * Controlador que atiende la acción admin-post de sincronización y muestra
 * notices tras la operación.
 *
 * @name SyncController
 * @package WPDM\Core\Infrastructure\WordPress\Admin\PostType\Controllers
 * @since 1.0.0
 */
class SyncController
{
    private const NOTICE_PREFIX = 'wpdm_project_sync_notice_';

    private ProjectSyncService $service;

    public function __construct(?ProjectSyncService $service = null)
    {
        $this->service = $service ?? new ProjectSyncService();
    }

    public function register(): void
    {
        \add_action('admin_post_' . SyncUrlBuilder::ACTION, [$this, 'handle']);
        \add_action('admin_notices', [$this, 'maybeShowNotice']);
    }

    public function handle(): void
    {
        if (!\current_user_can('edit_posts')) {
            \wp_die('No autorizado.');
        }

        $postId = (int) ($_GET['post'] ?? 0);
        $redir  = isset($_GET['redirect']) ? \esc_url_raw((string) \wp_unslash($_GET['redirect'])) : '';

        if ($postId <= 0) {
            \wp_die('Post inválido.');
        }

        \check_admin_referer(SyncUrlBuilder::nonceAction($postId));

        $result = $this->service->syncPost($postId);

        \set_transient($this->noticeKey(), [
            'type'    => $result['success'] ? 'success' : 'error',
            'message' => $result['message'],
        ], 30);

        if ($redir === '') {
            $redir = \admin_url('edit.php?post_type=' . ProjectsRepository::POST_TYPE);
        }
        \wp_safe_redirect($redir);
        exit;
    }

    public function maybeShowNotice(): void
    {
        $key    = $this->noticeKey();
        $notice = \get_transient($key);
        if (!\is_array($notice)) return;

        \delete_transient($key);

        $cls = ($notice['type'] ?? '') === 'success' ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . \esc_attr($cls) . ' is-dismissible"><p>'
            . \esc_html($notice['message'] ?? '')
            . '</p></div>';
    }

    private function noticeKey(): string
    {
        return self::NOTICE_PREFIX . \get_current_user_id();
    }
}
