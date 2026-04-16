<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\PostType;

use WPDM\Core\Infrastructure\WordPress\Admin\PostType\Columns\SyncColumn;
use WPDM\Core\Infrastructure\WordPress\Admin\PostType\Controllers\SyncController;
use WPDM\Core\Infrastructure\WordPress\Admin\PostType\MetaBoxes\DataMetaBox;
use WPDM\Core\Infrastructure\WordPress\Admin\PostType\MetaBoxes\SyncMetaBox;

/**
 * Punto de entrada de la experiencia admin del CPT "proyecto": enlaza los
 * componentes especializados (columna, meta boxes, controller) con WordPress
 * delegando el register() en cada uno. La lógica concreta vive en cada clase.
 *
 * @name ProjectAdminHooks
 * @package WPDM\Core\Infrastructure\WordPress\Admin\PostType
 * @since 1.0.0
 */
class ProjectAdminHooks
{
    public function __construct(
        private SyncColumn $column,
        private SyncMetaBox $sideBox,
        private DataMetaBox $dataBox,
        private SyncController $controller,
    ) {}

    public function register(): void
    {
        $this->column->register();
        $this->sideBox->register();
        $this->dataBox->register();
        $this->controller->register();
    }
}
