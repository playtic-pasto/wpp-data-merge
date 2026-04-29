<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Database;

/**
 * Migraciones de base de datos del plugin. Actualmente no se crean tablas custom
 * porque los datos agregados se almacenan como post_meta del CPT "proyecto".
 *
 * Se mantienen los nombres constantes por compatibilidad y por si se retoma el
 * almacenamiento relacional.
 *
 * @name WPDM_Database
 * @package WPDM\Core\Infrastructure\Database
 * @since 1.0.0
 */
class WPDM_Database
{
    public const SYNC_STATUS_ACTIVE  = 'active';
    public const SYNC_STATUS_PENDING = 'pending';
    public const SYNC_STATUS_ERROR   = 'error';

    /** @var list<string> */
    public const SYNC_STATUSES = [
        self::SYNC_STATUS_ACTIVE,
        self::SYNC_STATUS_PENDING,
        self::SYNC_STATUS_ERROR,
    ];

    /**
     * No crea tablas. Se mantiene idempotente para futuras migraciones.
     */
    public static function migrate(): void
    {
        // Intencionalmente vacío: toda la persistencia es vía post_meta.
    }

    public static function isValidSyncStatus(string $status): bool
    {
        return \in_array($status, self::SYNC_STATUSES, true);
    }
}
