<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Support;

/**
 * Construye URLs firmadas para disparar la acción admin-post de sincronización,
 * y provee las constantes de acción/nonce reutilizadas por el controller.
 *
 * @name SyncUrlBuilder
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Support
 * @since 1.0.0
 */
class SyncUrlBuilder
{
    public const ACTION = 'wpdm_sync_project';

    public static function build(int $postId, string $redirect = ''): string
    {
        return \wp_nonce_url(
            \add_query_arg([
                'action'   => self::ACTION,
                'post'     => $postId,
                'redirect' => \urlencode($redirect),
            ], \admin_url('admin-post.php')),
            self::nonceAction($postId)
        );
    }

    public static function nonceAction(int $postId): string
    {
        return self::ACTION . '_' . $postId;
    }
}
