<?php

declare(strict_types=1);

namespace WPDM\Bootstrap;

use WPDM\Core\Infrastructure\Database\WPDM_Database;
use WPDM\Shared\Helpers\UserHelper;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Acciones de ciclo de vida del plugin (activación/desactivación). Invocadas
 * por los hooks register_activation_hook/register_deactivation_hook.
 *
 * @name Lifecycle
 * @package WPDM\Bootstrap
 * @since 1.0.0
 */
final class Lifecycle
{
    public static function activate(): void
    {
        WPDM_Database::migrate();
        (new WPDM_Logger(WPDM_PATH))->info('Plugin activado por: ' . UserHelper::getCurrentUserLabel());
    }

    public static function deactivate(): void
    {
        (new WPDM_Logger(WPDM_PATH))->info('Plugin desactivado por: ' . UserHelper::getCurrentUserLabel());
    }
}
