<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Support;

/**
 * Utilidades de formato (dinero, área, fecha) compartidas por las vistas del admin.
 *
 * @name Formatter
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Support
 * @since 1.0.0
 */
class Formatter
{
    public static function money(mixed $value): string
    {
        if ($value === null || $value === '' || !\is_numeric($value)) return '—';
        return \number_format((float) $value, 0, ',', '.');
    }

    public static function area(mixed $value): string
    {
        if ($value === null || $value === '' || !\is_numeric($value)) return '—';
        return \number_format((float) $value, 2, ',', '.') . ' m²';
    }

    public static function dateTime(?string $raw, string $format = 'Y-m-d H:i'): string
    {
        if ($raw === null || $raw === '') return '—';
        $ts = \strtotime($raw);
        return $ts ? \date_i18n($format, $ts) : $raw;
    }
}
