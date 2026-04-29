<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Acf;

use WPDM\Core\Infrastructure\Api\SincoApiClient;

/**
 * Gestiona el catálogo dinámico de estados y tipos de unidad.
 *
 * Los tipos de unidad se obtienen del endpoint /TipoUnidad de SINCO.
 * Los estados se descubren automáticamente al sincronizar proyectos.
 * Ambos se guardan en wp_options para no consultar la API cada vez.
 *
 * @see SyncFiltersFieldGroup  Usa este catálogo para generar los checkboxes.
 * @see ProjectSyncService     Llama a discoverFromUnits() al sincronizar.
 */
class SyncCatalog
{
    /** wp_options key para los estados descubiertos */
    public const OPTION_STATUSES = 'wpdm_known_statuses';

    /** wp_options key para los tipos de unidad */
    public const OPTION_TYPES = 'wpdm_known_types';

    private SincoApiClient $sincoClient;

    public function __construct(?SincoApiClient $sincoClient = null)
    {
        $this->sincoClient = $sincoClient ?? new SincoApiClient();
    }

    // -------------------------------------------------------------------------
    //  Lectura — usados por SyncFiltersFieldGroup para los checkboxes
    // -------------------------------------------------------------------------

    /**
     * Obtiene los estados conocidos como array asociativo [valor => etiqueta].
     *
     * @return array<string, string>
     */
    public function getStatuses(): array
    {
        $statuses = get_option(self::OPTION_STATUSES, []);

        return is_array($statuses) ? $statuses : [];
    }

    /**
     * Obtiene los tipos de unidad conocidos como array asociativo [valor => etiqueta].
     *
     * @return array<string, string>
     */
    public function getTypes(): array
    {
        $types = get_option(self::OPTION_TYPES, []);

        return is_array($types) ? $types : [];
    }

    // -------------------------------------------------------------------------
    //  Descubrimiento — llamado durante la sincronización
    // -------------------------------------------------------------------------

    /**
     * Examina un conjunto de unidades y agrega estados/tipos nuevos al catálogo.
     *
     * Se llama desde ProjectSyncService después de recibir las unidades de la API.
     * Si encuentra un valor que no está en el catálogo, lo agrega automáticamente.
     *
     * @param array<int, array<string, mixed>> $units Unidades crudas de la API.
     */
    public function discoverFromUnits(array $units): void
    {
        $currentStatuses = $this->getStatuses();
        $currentTypes = $this->getTypes();
        $statusesChanged = false;
        $typesChanged = false;

        foreach ($units as $unit) {
            $status = trim((string) ($unit['estado'] ?? ''));
            if ($status !== '' && !isset($currentStatuses[$status])) {
                $currentStatuses[$status] = $status;
                $statusesChanged = true;
            }

            $type = trim((string) ($unit['tipoUnidad'] ?? ''));
            if ($type !== '' && !isset($currentTypes[$type])) {
                $currentTypes[$type] = $type;
                $typesChanged = true;
            }
        }

        if ($statusesChanged) {
            ksort($currentStatuses);
            update_option(self::OPTION_STATUSES, $currentStatuses, false);
        }

        if ($typesChanged) {
            ksort($currentTypes);
            update_option(self::OPTION_TYPES, $currentTypes, false);
        }
    }

    // -------------------------------------------------------------------------
    //  Carga desde la API — para tipos de unidad
    // -------------------------------------------------------------------------

    /**
     * Consulta el endpoint /TipoUnidad de SINCO y guarda los tipos en wp_options.
     *
     * Se ejecuta una sola vez (al visitar la página de filtros por primera vez)
     * o cuando el usuario lo solicite manualmente.
     *
     * @return bool true si se cargaron correctamente, false si hubo error.
     */
    public function fetchTypesFromApi(): bool
    {
        $response = $this->sincoClient->getTiposUnidad();

        if (\is_wp_error($response)) {
            return false;
        }

        $types = $this->getTypes();

        foreach ($response as $item) {
            $name = trim((string) ($item['nombre'] ?? ''));
            if ($name !== '' && !isset($types[$name])) {
                $types[$name] = $name;
            }
        }

        ksort($types);
        update_option(self::OPTION_TYPES, $types, false);

        return true;
    }

    /**
     * Verifica si ya se cargaron los tipos desde la API.
     */
    public function hasTypes(): bool
    {
        return !empty($this->getTypes());
    }

    /**
     * Verifica si ya se descubrieron estados.
     */
    public function hasStatuses(): bool
    {
        return !empty($this->getStatuses());
    }
}
