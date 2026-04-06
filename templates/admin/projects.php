<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap wpdm-wrap">
    <h1>Proyectos</h1>
    <p>Listado de proyectos sincronizados desde el ERP SINCO.</p>

    <div class="wpdm-section">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h2 style="margin: 0; border: none; padding: 0;">Proyectos sincronizados</h2>
            <button type="button" class="button button-primary" disabled>Sincronizar ahora</button>
        </div>

        <table class="widefat striped">
            <thead>
                <tr>
                    <th>ID Externo</th>
                    <th>Nombre del proyecto</th>
                    <th>Estado</th>
                    <th>Última actualización</th>
                    <th>Post WP</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #646970;">
                        No hay proyectos sincronizados.<br>
                        Configura la <a href="<?php echo esc_url(admin_url('admin.php?page=wpdm-settings')); ?>">conexión API</a>
                        y activa el <a href="<?php echo esc_url(admin_url('admin.php?page=wpdm-cron')); ?>">Cron Job</a>
                        para comenzar la sincronización.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
