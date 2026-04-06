<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap wpdm-wrap">
    <h1>Soporte</h1>
    <p>Información del plugin, preguntas frecuentes y datos de contacto.</p>

    <div class="wpdm-cards">
        <!-- Información del plugin -->
        <div class="wpdm-card">
            <h2>Información del plugin</h2>
            <table class="wpdm-cron-config">
                <tr>
                    <th>Nombre</th>
                    <td>WP Data Merge</td>
                </tr>
                <tr>
                    <th>Versión</th>
                    <td><?php echo esc_html(WPDM_VERSION); ?></td>
                </tr>
                <tr>
                    <th>Autor</th>
                    <td>PlayTIC</td>
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
                    <th>Directorio</th>
                    <td><code><?php echo esc_html(WPDM_PATH); ?></code></td>
                </tr>
            </table>
        </div>

        <!-- Entorno -->
        <div class="wpdm-card">
            <h2>Entorno del servidor</h2>
            <table class="wpdm-cron-config">
                <tr>
                    <th>Sistema operativo</th>
                    <td><?php echo esc_html(PHP_OS); ?></td>
                </tr>
                <tr>
                    <th>Servidor web</th>
                    <td><?php echo esc_html($_SERVER['SERVER_SOFTWARE'] ?? 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Zona horaria</th>
                    <td><?php echo esc_html(wp_timezone_string()); ?></td>
                </tr>
                <tr>
                    <th>Hora del servidor</th>
                    <td><?php echo esc_html(current_time('Y-m-d H:i:s')); ?></td>
                </tr>
                <tr>
                    <th>Memoria PHP</th>
                    <td><?php echo esc_html(ini_get('memory_limit')); ?></td>
                </tr>
                <tr>
                    <th>cURL habilitado</th>
                    <td><?php echo function_exists('curl_version') ? 'Sí' : 'No'; ?></td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Preguntas frecuentes -->
    <div class="wpdm-section">
        <h2>Preguntas frecuentes</h2>

        <div class="wpdm-faq-item">
            <h3>¿Qué hace este plugin?</h3>
            <p>WP Data Merge conecta tu sitio WordPress con el ERP SINCO para sincronizar automáticamente proyectos inmobiliarios. Los datos se obtienen vía API REST y se almacenan como contenido en WordPress.</p>
        </div>

        <div class="wpdm-faq-item">
            <h3>¿Cómo configuro la conexión con la API?</h3>
            <p>Ve a <strong>WP Data Merge &gt; Conexión API</strong> en el menú lateral. Allí podrás ingresar el endpoint, la clave de API y las credenciales de acceso proporcionadas por el administrador del ERP.</p>
        </div>

        <div class="wpdm-faq-item">
            <h3>¿Con qué frecuencia se sincronizan los datos?</h3>
            <p>La frecuencia se configura en <strong>WP Data Merge &gt; Cron Job</strong>. Puedes elegir intervalos desde cada 5 minutos hasta una vez al día. También puedes ejecutar una sincronización manual desde la página de Proyectos.</p>
        </div>

        <div class="wpdm-faq-item">
            <h3>¿Qué pasa si la API no responde?</h3>
            <p>El plugin registra todos los errores en el historial del Cron Job. Los datos existentes no se modifican si ocurre un error durante la sincronización. Puedes revisar los detalles del error en la sección de errores.</p>
        </div>

        <div class="wpdm-faq-item">
            <h3>¿Se pierden los datos al desactivar el plugin?</h3>
            <p>No. Al desactivar el plugin los datos permanecen en la base de datos. Solo se eliminan completamente si desinstalas el plugin desde WordPress.</p>
        </div>

        <div class="wpdm-faq-item">
            <h3>¿Dónde puedo ver los logs del plugin?</h3>
            <p>Los registros de actividad se almacenan en <code>wp-content/plugins/wpp-data-merge/logs/wpdm.log</code>. Puedes acceder a este archivo por FTP o desde el panel de control del servidor.</p>
        </div>
    </div>
</div>
