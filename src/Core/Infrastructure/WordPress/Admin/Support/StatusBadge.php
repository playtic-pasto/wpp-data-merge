<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Support;

use WPDM\Core\Infrastructure\Database\WPDM_Database;

/**
 * Renderiza un badge HTML con el estado de sincronización, usando los mismos
 * colores en todos los puntos del admin (columna, meta boxes).
 *
 * @name StatusBadge
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Support
 * @since 1.0.0
 */
class StatusBadge
{
    public static function render(string $status): string
    {
        [$label, $bg, $fg] = match ($status) {
            WPDM_Database::SYNC_STATUS_ACTIVE => ['Sincronizado', '#d1fae5', '#065f46'],
            WPDM_Database::SYNC_STATUS_PENDING => ['Pendiente', '#fef3c7', '#92400e'],
            WPDM_Database::SYNC_STATUS_ERROR => ['Error', '#fee2e2', '#991b1b'],
            default => ['Sin sincronizar', '#e5e7eb', '#374151'],
        };

        return \sprintf(
            '<span style="display:inline-block;padding:2px 10px;border-radius:10px;background:%s;color:%s;font-weight:600;font-size:11px;line-height:18px;">%s</span>',
            \esc_attr($bg),
            \esc_attr($fg),
            \esc_html($label)
        );
    }
}
