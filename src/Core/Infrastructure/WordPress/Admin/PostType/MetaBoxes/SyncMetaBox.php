<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\PostType\MetaBoxes;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\Api\CredentialLoader;
use WPDM\Core\Infrastructure\Database\WPDM_Database;
use WPDM\Core\Infrastructure\WordPress\Admin\Support\Formatter;
use WPDM\Core\Infrastructure\WordPress\Admin\Support\StatusBadge;
use WPDM\Core\Infrastructure\WordPress\Admin\Support\SyncUrlBuilder;

/**
 * Meta box lateral ("Sincronización SINCO") con estado, fecha, resumen y botón
 * de disparo.
 *
 * @name SyncMetaBox
 * @package WPDM\Core\Infrastructure\WordPress\Admin\PostType\MetaBoxes
 * @since 1.0.0
 */
class SyncMetaBox
{
    private const POST_TYPE = ProjectsRepository::POST_TYPE;
    private const ID = 'wpdm-sync-side';

    private CredentialLoader $credentials;

    public function __construct(?CredentialLoader $credentials = null)
    {
        $this->credentials = $credentials ?? new CredentialLoader();
    }

    public function register(): void
    {
        \add_action('add_meta_boxes_' . self::POST_TYPE, [$this, 'addMetaBox']);
    }

    public function addMetaBox(): void
    {
        \add_meta_box(self::ID, 'Sincronización SINCO', [$this, 'render'], self::POST_TYPE, 'side', 'high');
    }

    public function render(\WP_Post $post): void
    {
        $status = (string) \get_post_meta($post->ID, ProjectSyncService::META_SYNC_STATUS, true);
        $syncedAt = (string) \get_post_meta($post->ID, ProjectSyncService::META_LAST_SYNCED, true);
        $error = (string) \get_post_meta($post->ID, ProjectSyncService::META_LAST_ERROR, true);
        $agg = ProjectSyncService::readSummary($post->ID);

        $canSync = $this->credentials->loadCredentials() !== null;
        $url = SyncUrlBuilder::build($post->ID, \get_edit_post_link($post->ID, ''));

        echo '<p><strong>Estado:</strong><br>' . StatusBadge::render($status) . '</p>';

        if ($syncedAt !== '') {
            echo '<p><strong>Última sincronización:</strong><br>'
                . \esc_html(Formatter::dateTime($syncedAt)) . '</p>';
        } else {
            echo '<p style="color:#646970;">Aún no se ha sincronizado.</p>';
        }

        if (!empty($agg['units_total'])) {
            echo '<p><strong>Resumen:</strong><br>';
            echo \esc_html(\sprintf('%d unidades', (int) $agg['units_total']));
            if (!empty($agg['projects_count'])) {
                echo \esc_html(\sprintf(' · %d proyecto(s)', (int) $agg['projects_count']));
            }
            echo '<br>';
            echo \esc_html('Precio: $' . Formatter::money($agg['price_min'] ?? null)
                . ' — $' . Formatter::money($agg['price_max'] ?? null));
            echo '</p>';
        }

        if ($status === WPDM_Database::SYNC_STATUS_ERROR && $error !== '') {
            echo '<p style="color:#991b1b;"><strong>Último error:</strong><br>' . \esc_html($error) . '</p>';
        }

        if ($canSync) {
            echo '<a href="' . \esc_url($url) . '" class="button button-primary wpdm-sync-project" '
                . 'data-title="' . \esc_attr(\get_the_title($post)) . '" style="width:100%;text-align:center;">'
                . ($syncedAt === '' ? 'Sincronizar ahora' : 'Re-sincronizar')
                . '</a>';
        } else {
            echo '<p style="color:#b45309;">Configura la <a href="'
                . \esc_url(\admin_url('admin.php?page=wpdm-settings')) . '">conexión API</a> antes de sincronizar.</p>';
        }

        $this->printConfirmScript();
    }

    private function printConfirmScript(): void
    {
        static $printed = false;
        if ($printed)
            return;
        $printed = true;
        ?>
        <script>
            (function () {
                document.addEventListener('click', function (e) {
                    var a = e.target.closest('a.wpdm-sync-project');
                    if (!a) return;
                    var title = a.dataset.title || 'este proyecto';
                    if (!window.confirm('¿Sincronizar "' + title + '" con los datos actuales de SINCO?')) {
                        e.preventDefault();
                    }
                });
            })();
        </script>
        <?php
    }
}
