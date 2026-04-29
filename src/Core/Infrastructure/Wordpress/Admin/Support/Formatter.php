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
        if ($value === null || $value === '' || !\is_numeric($value))
            return '—';
        return \number_format((float) $value, 0, ',', '.');
    }

    public static function area(mixed $value): string
    {
        if ($value === null || $value === '' || !\is_numeric($value))
            return '—';
        return \number_format((float) $value, 2, ',', '.') . ' m²';
    }

    /**
     * Formatea un timestamp Unix (UTC) en la zona horaria de WordPress.
     * Úsalo cuando ya tienes un timestamp (wp_next_scheduled, time(), etc.).
     */
    public static function timestamp(?int $ts, string $format = 'Y-m-d H:i'): string
    {
        if ($ts === null || $ts <= 0) return '—';
        return \function_exists('wp_date') ? \wp_date($format, $ts) : \date_i18n($format, $ts);
    }

    /**
     * Formatea una fecha respetando la zona horaria configurada en WordPress.
     * Usa DateTimeImmutable + DateTimeZone para garantizar una conversión
     * correcta (WP_TIMEZONE_STRING o Settings > General).
     */
    public static function dateTime(?string $raw, string $format = 'Y-m-d H:i'): string
    {
        if ($raw === null || $raw === '')
            return '—';

        try {
            $tz = \function_exists('wp_timezone')
                ? \wp_timezone()
                : new \DateTimeZone(\date_default_timezone_get() ?: 'UTC');

            $dt = new \DateTimeImmutable($raw, $tz);
            $dt = $dt->setTimezone($tz);

            return \function_exists('wp_date')
                ? \wp_date($format, $dt->getTimestamp())
                : \date_i18n($format, $dt->getTimestamp() + $dt->getOffset());
        } catch (\Exception $e) {
            return $raw;
        }
    }
}
