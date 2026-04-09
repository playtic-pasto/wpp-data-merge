<?php
if (!defined('ABSPATH')) exit;

$server_time = current_time('Y-m-d H:i:s');
$timezone     = wp_timezone_string();
?>
<div class="wrap wpdm-wrap">
    <h1>Cron Job — Sincronización Automática</h1>
    <p>Administra la tarea programada que sincroniza los datos desde el ERP.</p>

    <!-- Configuración del Cron -->
    <div class="wpdm-section">
        <h2>Configuración</h2>
        <form method="post" action="">
            <table class="form-table wpdm-form-table">
                <tr>
                    <th scope="row">Estado</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpdm_cron_active" value="1" disabled />
                            Activar sincronización automática
                        </label>
                        <p class="description">Al activar, el cron se ejecutará según el intervalo configurado.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpdm_cron_name">Nombre de la tarea</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wpdm_cron_name"
                            name="wpdm_cron_name"
                            value="wpdm_cron_sync"
                            class="regular-text"
                            readonly
                        />
                        <p class="description">Identificador interno de la tarea programada.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpdm_cron_interval">Intervalo de ejecución</label>
                    </th>
                    <td>
                        <select id="wpdm_cron_interval" name="wpdm_cron_interval" disabled>
                            <option value="every_5_minutes">Cada 5 minutos</option>
                            <option value="every_15_minutes">Cada 15 minutos</option>
                            <option value="every_30_minutes">Cada 30 minutos</option>
                            <option value="hourly" selected>Cada hora</option>
                            <option value="twicedaily">Dos veces al día</option>
                            <option value="daily">Diario</option>
                        </select>
                        <p class="description">Frecuencia con la que se ejecutará la sincronización.</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Guardar configuración', 'primary', 'wpdm_save_cron', true, ['disabled' => 'disabled']); ?>
            <p class="description">La configuración del cron estará disponible próximamente.</p>
        </form>
    </div>

    <!-- Estado actual -->
    <div class="wpdm-section">
        <h2>Estado actual</h2>
        <table class="wpdm-cron-config">
            <tr>
                <th>Estado</th>
                <td><span class="wpdm-badge wpdm-badge--inactive">Inactivo</span></td>
            </tr>
            <tr>
                <th>Última sincronización</th>
                <td>—</td>
            </tr>
            <tr>
                <th>Próxima ejecución</th>
                <td>—</td>
            </tr>
            <tr>
                <th>Hora del servidor</th>
                <td><?php echo esc_html($server_time); ?> (<?php echo esc_html($timezone); ?>)</td>
            </tr>
        </table>
    </div>

    <!-- Historial de ejecuciones -->
    <div class="wpdm-section">
        <h2>Historial de ejecuciones</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Duración</th>
                    <th>Registros procesados</th>
                    <th>Estado</th>
                    <th>Mensaje</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="5" style="text-align: center; padding: 20px; color: #646970;">
                        No hay ejecuciones registradas aún.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Errores de sincronización -->
    <div class="wpdm-section">
        <h2>Errores de sincronización</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo de error</th>
                    <th>Mensaje</th>
                    <th>Datos afectados</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="4" style="text-align: center; padding: 20px; color: #646970;">
                        No se han registrado errores.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
