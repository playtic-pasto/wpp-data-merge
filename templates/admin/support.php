<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap wpdm-wrap">
    <h1>Soporte</h1>
    <p>Información del plugin, preguntas frecuentes.</p>

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
                    <td>PlayTIC - Soluciones Digitales</td>    
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
            <p>WPP Data Merge conecta tu sitio WordPress con el ERP SINCO para sincronizar automáticamente proyectos inmobiliarios. Los datos se obtienen vía API REST y se almacenan como contenido en WordPress.</p>
        </div>

        <div class="wpdm-faq-item">
            <h3>¿Cómo configuro la conexión con la API?</h3>
            <p>Ve a <strong>WPP Data Merge &gt; Conexión API</strong> en el menú lateral. Allí podrás configurar la conexión hacia Cinco, el endpoint, la clave de API y las credenciales de acceso proporcionadas por el administrador del ERP.</p>
            <p>Todos los campos configurados son encriptados y almacenados de manera segura.</p>
        </div>

        <div class="wpdm-faq-item">
            <h3>¿Con qué frecuencia se sincronizan los datos?</h3>
            <p>La frecuencia de sincronización se configura en <strong>WPP Data Merge &gt; Cron Job</strong>, donde puedes definir intervalos que van desde cada 5 minutos hasta una vez al día. Además, es posible ejecutar una sincronización manual para un proyecto específico directamente desde la página de detalles del proyecto.</p>
        </div>

        <div class="wpdm-faq-item">
            <h3>¿Qué ocurre si la API no responde?</h3>
            <p>Si la API no responde, el plugin registra el error en el historial del Cron Job. En estos casos, los datos existentes se mantienen sin cambios para evitar inconsistencias durante la sincronización.</p>
            <p>Puedes revisar el detalle del error en la sección correspondiente. Para un análisis más técnico, también es posible consultar los logs asociados al plugin.</p>
        </div>

        <div class="wpdm-faq-item">
            <h3>¿Se pierden los datos al desactivar el plugin?</h3>
            <p>No. Al desactivar el plugin, los datos permanecen almacenados en la base de datos. Solo se eliminan por completo si el plugin se desinstala desde WordPress.</p>
        </div>

        <div class="wpdm-faq-item">
            <h3>¿Dónde puedo consultar los logs del plugin?</h3>
            <p>Los registros de actividad se almacenan en el archivo <code>wp-content/plugins/wpp-data-merge/logs/wpdm.log</code>. Puedes acceder a este archivo mediante FTP o desde el panel de control de tu servidor.</p>
        </div>
    </div>
</div>
