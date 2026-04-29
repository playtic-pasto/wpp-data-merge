<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\PostType;

use WPDM\Core\Infrastructure\WordPress\Admin\PostType\Columns\SyncColumn;
use WPDM\Core\Infrastructure\WordPress\Admin\PostType\Controllers\SyncController;
use WPDM\Core\Infrastructure\WordPress\Admin\PostType\MetaBoxes\SyncMetaBox;

/**
 * Enlaza los componentes del admin del CPT "proyecto" con WordPress:
 * columna de sincronización, meta box lateral y controller de sync.
 *
 * Los datos sincronizados se muestran en campos ACF individuales
 * registrados por SyncDataFieldGroup.
 *
 * @see SyncDataFieldGroup  Campos ACF con los datos sincronizados.
 */
class ProjectAdminHooks
{
    public function __construct(
        private SyncColumn $column,
        private SyncMetaBox $sideBox,
        private SyncController $controller,
    ) {}

    public function register(): void
    {
        $this->column->register();
        $this->sideBox->register();
        $this->controller->register();
    }
}
