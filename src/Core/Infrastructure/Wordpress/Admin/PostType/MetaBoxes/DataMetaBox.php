<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\PostType\MetaBoxes;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\WordPress\Admin\Support\Formatter;

/**
 * Meta box inferior ("Datos sincronizados") con totales consolidados, conteos
 * por estado/tipo y desglose por id_proyecto.
 *
 * @name DataMetaBox
 * @package WPDM\Core\Infrastructure\WordPress\Admin\PostType\MetaBoxes
 * @since 1.0.0
 */
class DataMetaBox
{
    private const POST_TYPE = ProjectsRepository::POST_TYPE;
    private const ID = 'wpdm-sync-data';

    public function register(): void
    {
        \add_action('add_meta_boxes_' . self::POST_TYPE, [$this, 'addMetaBox']);
    }

    public function addMetaBox(): void
    {
        \add_meta_box(self::ID, 'Datos sincronizados', [$this, 'render'], self::POST_TYPE, 'normal', 'default');
    }

    public function render(\WP_Post $post): void
    {
        $agg       = ProjectSyncService::readSummary($post->ID);
        $breakdown = ProjectSyncService::readByProject($post->ID);

        if (empty($agg)) {
            echo '<p style="color:#646970;">Aún no se ha sincronizado este proyecto. Usa el botón de la tarjeta lateral para traer los datos desde SINCO.</p>';
            return;
        }

        $this->renderTotals($agg);
        $this->renderCounts('Por estado', $agg['by_status'] ?? []);
        $this->renderCounts('Por tipo de unidad', $agg['by_type'] ?? []);

        if (\count($breakdown) > 1) {
            $this->renderBreakdown($breakdown);
        }
    }

    private function renderTotals(array $agg): void
    {
        echo '<h3 style="margin-top:0;">Totales consolidados</h3>';
        echo '<table class="widefat striped" style="max-width:720px;"><tbody>';
        $this->row('Unidades totales', (string) (int) ($agg['units_total'] ?? 0));
        $this->row('Precio mínimo', '$' . Formatter::money($agg['price_min'] ?? null));
        $this->row('Precio máximo', '$' . Formatter::money($agg['price_max'] ?? null));
        $this->row('Precio promedio', '$' . Formatter::money($agg['price_avg'] ?? null));
        $this->row('Precio total inventario', '$' . Formatter::money($agg['price_total'] ?? null));
        $this->row(
            'Área privada (min/max/prom)',
            Formatter::area($agg['private_area_min'] ?? null) . ' / '
                . Formatter::area($agg['private_area_max'] ?? null) . ' / '
                . Formatter::area($agg['private_area_avg'] ?? null)
        );
        $this->row(
            'Área construida (min/max)',
            Formatter::area($agg['built_area_min'] ?? null) . ' / '
                . Formatter::area($agg['built_area_max'] ?? null)
        );
        echo '</tbody></table>';
    }

    private function renderCounts(string $title, array $counts): void
    {
        if (empty($counts)) return;
        echo '<h3>' . \esc_html($title) . '</h3>';
        echo '<table class="widefat striped" style="max-width:420px;"><tbody>';
        foreach ($counts as $k => $v) {
            $this->row((string) $k, (string) (int) $v);
        }
        echo '</tbody></table>';
    }

    private function renderBreakdown(array $breakdown): void
    {
        echo '<h3>Desglose por id_proyecto</h3>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>ID Proyecto</th><th>Unidades</th><th>Precio min</th><th>Precio max</th><th>Precio prom</th>';
        echo '</tr></thead><tbody>';
        foreach ($breakdown as $id => $b) {
            echo '<tr>';
            echo '<td>' . \esc_html((string) $id) . '</td>';
            if (isset($b['error'])) {
                echo '<td colspan="4" style="color:#991b1b;">' . \esc_html((string) $b['error']) . '</td>';
            } else {
                echo '<td>' . \esc_html((string) (int) ($b['units_total'] ?? 0)) . '</td>';
                echo '<td>$' . \esc_html(Formatter::money($b['price_min'] ?? null)) . '</td>';
                echo '<td>$' . \esc_html(Formatter::money($b['price_max'] ?? null)) . '</td>';
                echo '<td>$' . \esc_html(Formatter::money($b['price_avg'] ?? null)) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    private function row(string $label, string $value): void
    {
        echo '<tr><td style="width:240px;"><strong>' . \esc_html($label) . '</strong></td><td>' . \esc_html($value) . '</td></tr>';
    }
}
