<?php
if (!defined('ABSPATH'))
    exit;

$endpoint = get_option('wpdm_api_endpoint', '');
$has_user = !empty(get_option('wpdm_api_user', ''));
$has_password = !empty(get_option('wpdm_api_password', ''));
?>
<div class="wrap wpdm-wrap">
    <h1>Ajustes de Conexión API</h1>
    <p>Configura las credenciales para conectar con la API del ERP SINCO.</p>

    <?php if (isset($_GET['wpdm_status'])): ?>
        <?php if ($_GET['wpdm_status'] === 'saved'): ?>
            <div class="notice notice-success is-dismissible">
                <p>Ajustes guardados correctamente.</p>
            </div>
        <?php
    elseif ($_GET['wpdm_status'] === 'error'): ?>
            <div class="notice notice-error is-dismissible">
                <p>Error al guardar los ajustes. Verifica los datos e intenta de nuevo.</p>
            </div>
        <?php
    endif; ?>
    <?php
endif; ?>

    <div class="wpdm-section">
        <h2>Credenciales de autenticación</h2>
        <p>Estas credenciales se envían en el body de la petición al endpoint de autenticación.</p>
        <form method="post" action="">
            <?php wp_nonce_field('wpdm_save_settings', 'wpdm_settings_nonce'); ?>
            <table class="form-table wpdm-form-table">
                <tr>
                    <th scope="row">
                        <label for="wpdm_api_endpoint">Endpoint de la API</label>
                    </th>
                    <td>
                        <input
                            type="url"
                            id="wpdm_api_endpoint"
                            name="wpdm_api_endpoint"
                            value="<?php echo esc_attr($endpoint); ?>"
                            class="regular-text"
                            placeholder="https://ejemplo.com/api"
                        />
                        <p class="description">URL del endpoint de autenticación de SINCO.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpdm_api_user">Usuario</label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="wpdm_api_user"
                            name="wpdm_api_user"
                            value=""
                            class="regular-text"
                            placeholder="<?php echo $has_user ? '••••••••' : 'Nombre de usuario'; ?>"
                        />
                        
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpdm_api_password">Contraseña </label>
                    </th>
                    <td>
                        <textarea
                            id="wpdm_api_password"
                            name="wpdm_api_password"
                            class="large-text"
                            rows="4"
                            placeholder="<?php echo $has_password ? '••••••••••••••••' : 'Clave del usuario'; ?>"
                        ></textarea>
                    </td>
                </tr>
            </table>

            <?php submit_button('Guardar ajustes', 'primary', 'wpdm_save_settings'); ?>
        </form>
    </div>

    <div class="wpdm-section">
        <h2>Probar conexión</h2>
        <p>Verifica que las credenciales sean correctas realizando una conexión de prueba al endpoint configurado.</p>

        <?php
        $test_result = get_transient('wpdm_test_result');
        if ($test_result !== false):
            delete_transient('wpdm_test_result');
        ?>
            <?php if ($test_result['success']): ?>
                <div class="notice notice-success">
                    <p><strong><?php echo esc_html($test_result['message']); ?></strong></p>
                </div>
                <?php if (!empty($test_result['data'])): ?>
                    <table class="wpdm-cron-config" style="margin: 10px 0 15px;">
                        <tr>
                            <th>Usuario</th>
                            <td><?php echo esc_html($test_result['data']['usuario']); ?></td>
                        </tr>
                        <tr>
                            <th>ID Usuario</th>
                            <td><?php echo esc_html($test_result['data']['id_usuario']); ?></td>
                        </tr>
                        <tr>
                            <th>Tipo de token</th>
                            <td><?php echo esc_html($test_result['data']['token_type']); ?></td>
                        </tr>
                        <tr>
                            <th>Expira en</th>
                            <td><?php echo esc_html(gmdate('H\h i\m s\s', (int) $test_result['data']['expires_in'])); ?></td>
                        </tr>
                    </table>
                <?php endif; ?>
            <?php else: ?>
                <div class="notice notice-error">
                    <p><strong><?php echo esc_html($test_result['message']); ?></strong></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('wpdm_test_connection', 'wpdm_test_nonce'); ?>
            <?php submit_button('Probar conexión', 'secondary', 'wpdm_test_connection', false); ?>
        </form>
    </div>
</div>
