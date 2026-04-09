<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap wpdm-wrap">
    <h1>WP Data Merge</h1>
    <p>Plugin de sincronización de datos desde el ERP SINCO hacia WordPress.</p>

    <!-- Estadísticas de sincronización -->
    <div class="wpdm-stats">
        <div class="wpdm-stat">
            <span class="wpdm-stat-value">0</span>
            <span class="wpdm-stat-label">Proyectos sincronizados</span>
        </div>
        <div class="wpdm-stat wpdm-stat--success">
            <span class="wpdm-stat-value">0</span>
            <span class="wpdm-stat-label">Sincronizaciones exitosas</span>
        </div>
        <div class="wpdm-stat wpdm-stat--danger">
            <span class="wpdm-stat-value">0</span>
            <span class="wpdm-stat-label">Errores</span>
        </div>
        <div class="wpdm-stat wpdm-stat--warning">
            <span class="wpdm-stat-value">—</span>
            <span class="wpdm-stat-label">Última sincronización</span>
        </div>
    </div>

    <!-- Información del plugin -->
    <div class="wpdm-cards">
        <div class="wpdm-card">
            <h2>Acerca del plugin</h2>
            <p>
                <strong>WP Data Merge</strong> conecta tu sitio WordPress con el sistema ERP SINCO
                para sincronizar automáticamente la información de proyectos inmobiliarios.
            </p>
            <p>Los datos se obtienen mediante la API REST del ERP y se almacenan como posts personalizados en WordPress.</p>
        </div>

        <div class="wpdm-card">
            <h2>Funcionalidades</h2>
            <ul>
                <li>Conexión segura con la API de SINCO ERP</li>
                <li>Sincronización automática mediante Cron Job</li>
                <li>Gestión y visualización de proyectos</li>
                <li>Registro de actividad y errores</li>
                <li>Panel de estadísticas en tiempo real</li>
            </ul>
        </div>

        <div class="wpdm-card">
            <h2>Estado del sistema</h2>
            <table class="wpdm-cron-config">
                <tr>
                    <th>Versión del plugin</th>
                    <td><?php echo esc_html(WPDM_VERSION); ?></td>
                </tr>
                <tr>
                    <th>PHP</th>
                    <td><?php echo esc_html(phpversion()); ?></td>
                </tr>
                <tr>
                    <th>WordPress</th>
                    <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <th>Conexión API</th>
                    <td><span class="wpdm-badge wpdm-badge--pending">Sin configurar</span></td>
                </tr>
                <tr>
                    <th>Cron Job</th>
                    <td><span class="wpdm-badge wpdm-badge--inactive">Inactivo</span></td>
                </tr>
            </table>
        </div>
    </div>
</div>
