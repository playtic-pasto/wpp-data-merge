<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\Tables;

if (!class_exists('WP_List_Table')) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Lista de unidades SINCO renderizada con la API nativa de WordPress (WP_List_Table),
 * con paginación, orden por columna, búsqueda y acciones en lote.
 *
 * @name UnitsListTable
 * @package WPDM\Core\Infrastructure\WordPress\Admin\Tables
 * @since 1.0.0
 */
class UnitsListTable extends \WP_List_Table
{
    /** @var array<int, array<string, mixed>> */
    private array $source;

    /** @var array<int, array{id: int, label: string}> */
    private array $projectOptions;

    private int $perPage = 20;

    /**
     * @param array<int, array<string, mixed>> $items Unidades normalizadas con columnas planas.
     * @param array<int, array{id: int, label: string}> $projectOptions Opciones para el filtro de proyecto.
     */
    public function __construct(array $items = [], array $projectOptions = [])
    {
        parent::__construct([
            'singular' => 'unidad',
            'plural' => 'unidades',
            'ajax' => false,
            'screen' => 'wpdm-projects',
        ]);

        $this->source = $items;
        $this->projectOptions = $projectOptions;
    }

    /**
     * @return array<string, string>
     */
    public function get_columns(): array
    {
        return [
            'cb' => '<input type="checkbox" />',
            'nombre' => 'Unidad',
            'proyecto' => 'Proyecto',
            '_sync_status' => 'Sincronización',
            'tipoUnidad' => 'Tipo',
            'tipoInmueble' => 'Inmueble',
            'estado' => 'Estado',
            'valor' => 'Valor',
            'areaPrivada' => 'Área privada',
            'areaConstruida' => 'Área construida',
            'numeroPiso' => 'Piso',
            'fechaEntrega' => 'Entrega',
        ];
    }

    /**
     * @return array<string, array{0: string, 1: bool}>
     */
    protected function get_sortable_columns(): array
    {
        return [
            'nombre' => ['nombre', false],
            'proyecto' => ['proyecto', false],
            '_sync_status' => ['_sync_status', false],
            'tipoUnidad' => ['tipoUnidad', false],
            'estado' => ['estado', false],
            'valor' => ['valor', true],
            'areaPrivada' => ['areaPrivada', true],
            'areaConstruida' => ['areaConstruida', true],
            'numeroPiso' => ['numeroPiso', true],
            'fechaEntrega' => ['fechaEntrega', true],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function get_bulk_actions(): array
    {
        return [
            'sync' => 'Sincronizar',
            'refresh_cache' => 'Refrescar caché SINCO',
        ];
    }

    protected function column_cb($item): string
    {
        return sprintf('<input type="checkbox" name="unit[]" value="%s" />', esc_attr((string) ($item['id'] ?? '')));
    }

    protected function column_default($item, $column_name): string
    {
        $value = $item[$column_name] ?? '';

        if ($column_name === 'valor' && is_numeric($value)) {
            return '$' . number_format((float) $value, 0, ',', '.');
        }

        if ($column_name === 'fechaEntrega' && !empty($value)) {
            $ts = strtotime((string) $value);
            return $ts ? esc_html(date_i18n('Y-m-d', $ts)) : esc_html((string) $value);
        }

        if (is_bool($value)) {
            return $value ? 'Sí' : 'No';
        }

        return esc_html((string) $value);
    }

    protected function column_nombre($item): string
    {
        $unitId = (int) ($item['id'] ?? 0);
        $syncStatus = (string) ($item['_sync_status'] ?? '');

        $actions = [
            'id' => '<span>ID ' . esc_html((string) $unitId) . '</span>',
        ];

        if ($syncStatus !== 'active') {
            $syncUrl = wp_nonce_url(
                add_query_arg([
                    'page' => 'wpdm-projects',
                    'action' => 'sync_unit',
                    'unit' => $unitId,
                ], admin_url('admin.php')),
                'wpdm_sync_unit_' . $unitId
            );

            $label = $syncStatus === 'error' ? 'Reintentar' : 'Sincronizar';

            $actions['sync'] = sprintf(
                '<a href="%s" class="wpdm-sync-unit" data-unit="%s" data-name="%s">%s</a>',
                esc_url($syncUrl),
                esc_attr((string) $unitId),
                esc_attr((string) ($item['nombre'] ?? '')),
                esc_html($label)
            );
        }

        return '<strong>' . esc_html((string) ($item['nombre'] ?? '')) . '</strong>'
            . $this->row_actions($actions, true);
    }

    protected function column__sync_status($item): string
    {
        $status = (string) ($item['_sync_status'] ?? '');
        $syncedAt = (string) ($item['_synced_at'] ?? '');
        $error = (string) ($item['_last_error'] ?? '');

        [$label, $bg, $fg] = match ($status) {
            'active' => ['Sincronizado', '#d1fae5', '#065f46'],
            'pending' => ['Pendiente', '#fef3c7', '#92400e'],
            'error' => ['Error', '#fee2e2', '#991b1b'],
            default => ['Sin sincronizar', '#e5e7eb', '#374151'],
        };

        $badge = sprintf(
            '<span style="display:inline-block;padding:2px 10px;border-radius:10px;background:%s;color:%s;font-weight:600;font-size:11px;line-height:18px;white-space: nowrap;">%s</span>',
            esc_attr($bg),
            esc_attr($fg),
            esc_html($label)
        );

        $meta = '';
        if ($syncedAt !== '') {
            $ts = strtotime($syncedAt);
            $meta = '<div style="color:#646970;font-size:11px;margin-top:4px;">Última: '
                . esc_html($ts ? date_i18n('Y-m-d H:i', $ts) : $syncedAt)
                . '</div>';
        }

        if ($status === 'error' && $error !== '') {
            $meta .= '<div style="color:#991b1b;font-size:11px;margin-top:2px;" title="' . esc_attr($error) . '">'
                . esc_html(mb_strimwidth($error, 0, 60, '…'))
                . '</div>';
        }

        return $badge . $meta;
    }

    protected function extra_tablenav($which): void
    {
        if ($which !== 'top' || empty($this->projectOptions)) {
            return;
        }

        $selected = isset($_REQUEST['proyecto']) ? (int) $_REQUEST['proyecto'] : 0;
        ?>
        <div class="alignleft actions">
            <label for="filter-by-proyecto" class="screen-reader-text">Filtrar por proyecto</label>
            <select name="proyecto" id="filter-by-proyecto">
                <option value="0" <?php selected($selected, 0); ?>>Todos los proyectos</option>
                <?php foreach ($this->projectOptions as $opt): ?>
                    <option value="<?php echo esc_attr((string) $opt['id']); ?>" <?php selected($selected, $opt['id']); ?>>
                        <?php echo esc_html($opt['label']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php submit_button('Filtrar', 'secondary', 'filter_action', false); ?>
        </div>
        <?php
    }

    public function no_items(): void
    {
        esc_html_e('No hay unidades para mostrar. Verifica la configuración de los proyectos y la conexión API.', 'wpdm');
    }

    public function prepare_items(): void
    {
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns(), 'nombre'];

        $items = $this->source;

        $projectFilter = isset($_REQUEST['proyecto']) ? (int) $_REQUEST['proyecto'] : 0;
        if ($projectFilter > 0) {
            $items = array_values(array_filter($items, static function ($row) use ($projectFilter) {
                return (int) ($row['idProyecto'] ?? 0) === $projectFilter;
            }));
        }

        $search = isset($_REQUEST['s']) ? trim((string) wp_unslash($_REQUEST['s'])) : '';
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $items = array_values(array_filter($items, static function ($row) use ($needle) {
                foreach ($row as $v) {
                    if (is_scalar($v) && str_contains(mb_strtolower((string) $v), $needle)) {
                        return true;
                    }
                }
                return false;
            }));
        }

        $orderby = isset($_REQUEST['orderby']) ? (string) $_REQUEST['orderby'] : 'nombre';
        $order = isset($_REQUEST['order']) && strtolower((string) $_REQUEST['order']) === 'desc' ? 'desc' : 'asc';

        usort($items, static function ($a, $b) use ($orderby, $order) {
            $av = $a[$orderby] ?? '';
            $bv = $b[$orderby] ?? '';
            if (is_numeric($av) && is_numeric($bv)) {
                $cmp = ($av <=> $bv);
            } else {
                $cmp = strcasecmp((string) $av, (string) $bv);
            }
            return $order === 'desc' ? -$cmp : $cmp;
        });

        $total = count($items);
        $paged = max(1, (int) ($_REQUEST['paged'] ?? 1));
        $offset = ($paged - 1) * $this->perPage;
        $this->items = array_slice($items, $offset, $this->perPage);

        $this->set_pagination_args([
            'total_items' => $total,
            'per_page' => $this->perPage,
            'total_pages' => (int) ceil($total / max($this->perPage, 1)),
        ]);
    }
}
