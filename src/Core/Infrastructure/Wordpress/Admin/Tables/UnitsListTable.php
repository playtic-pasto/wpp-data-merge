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

    private int $perPage = 20;

    /**
     * @param array<int, array<string, mixed>> $items Unidades normalizadas con columnas planas.
     */
    public function __construct(array $items = [])
    {
        parent::__construct([
            'singular' => 'unidad',
            'plural' => 'unidades',
            'ajax' => false,
            'screen' => 'wpdm-projects',
        ]);

        $this->source = $items;
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
        return '<strong>' . esc_html((string) ($item['nombre'] ?? '')) . '</strong>'
            . '<div class="row-actions"><span>ID ' . esc_html((string) ($item['id'] ?? '')) . '</span></div>';
    }

    public function no_items(): void
    {
        esc_html_e('No hay unidades para mostrar. Verifica la configuración de los proyectos y la conexión API.', 'wpdm');
    }

    public function prepare_items(): void
    {
        $this->_column_headers = [$this->get_columns(), [], $this->get_sortable_columns(), 'nombre'];

        $items = $this->source;

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
