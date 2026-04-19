<?php
if (!defined('ABSPATH')) exit;

$endpoint     = get_option('wpdm_api_endpoint', '');
$has_user     = !empty(get_option('wpdm_api_user', ''));
$has_password = !empty(get_option('wpdm_api_password', ''));
$test_result  = get_transient('wpdm_test_result');
if ($test_result !== false) {
    delete_transient('wpdm_test_result');
}
?>
<div class="wrap wpdm-wrap">
    <h1>Conexión API</h1>
    <p class="wpdm-subtitle">Configura las credenciales para conectar con la API del ERP SINCO</p>

    <?php if (isset($_GET['wpdm_status'])): ?>
        <?php if ($_GET['wpdm_status'] === 'saved'): ?>
            <div class="notice notice-success is-dismissible"><p>Ajustes guardados correctamente.</p></div>
        <?php elseif ($_GET['wpdm_status'] === 'error'): ?>
            <div class="notice notice-error is-dismissible"><p>Error al guardar. Verifica los datos e intenta de nuevo.</p></div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="wpdm-cards">
        <!-- Credenciales -->
        <div class="wpdm-card">
            <h2><span class="dashicons dashicons-admin-network"></span> Credenciales</h2>
            <form method="post" action="">
                <?php wp_nonce_field('wpdm_save_settings', 'wpdm_settings_nonce'); ?>
                <table class="wpdm-status-table">
                    <tr>
                        <th><label for="wpdm_api_endpoint">Endpoint</label></th>
                        <td>
                            <input type="url" id="wpdm_api_endpoint" name="wpdm_api_endpoint"
                                   value="<?php echo esc_attr($endpoint); ?>"
                                   class="regular-text" placeholder="https://ejemplo.com/api"
                                   style="width: 100%;" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wpdm_api_user">Usuario</label></th>
                        <td>
                            <input type="text" id="wpdm_api_user" name="wpdm_api_user" value=""
                                   class="regular-text"
                                   placeholder="<?php echo $has_user ? '••••••••' : 'Nombre de usuario'; ?>"
                                   style="width: 100%;" />
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wpdm_api_password">Clave</label></th>
                        <td>
                            <textarea id="wpdm_api_password" name="wpdm_api_password"
                                      rows="3" style="width: 100%;"
                                      placeholder="<?php echo $has_password ? '••••••••••••••••' : 'Clave del usuario'; ?>"
                            ></textarea>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Guardar ajustes', 'primary', 'wpdm_save_settings'); ?>
            </form>
        </div>

        <!-- Probar conexión -->
        <div class="wpdm-card">
            <h2><span class="dashicons dashicons-yes-alt"></span> Probar conexión</h2>
            <p style="color: #646970; margin-top: 0;">Verifica que las credenciales sean correctas.</p>

            <?php if ($test_result !== false): ?>
                <?php if ($test_result['success']): ?>
                    <div class="wpdm-alert" style="background: #d4edda; border: 1px solid #b7dfc5; color: #0a5c1f; margin-bottom: 16px;">
                        <strong><?php echo esc_html($test_result['message']); ?></strong>
                    </div>
                    <?php if (!empty($test_result['data'])): ?>
                        <table class="wpdm-status-table">
                            <tr>
                                <th>Usuario</th>
                                <td><?php echo esc_html($test_result['data']['usuario']); ?></td>
                            </tr>
                            <tr>
                                <th>ID Usuario</th>
                                <td><?php echo esc_html($test_result['data']['id_usuario']); ?></td>
                            </tr>
                            <tr>
                                <th>Token</th>
                                <td><?php echo esc_html($test_result['data']['token_type']); ?></td>
                            </tr>
                            <tr>
                                <th>Expira en</th>
                                <td><?php echo esc_html(gmdate('H\h i\m s\s', (int) $test_result['data']['expires_in'])); ?></td>
                            </tr>
                        </table>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="wpdm-alert wpdm-alert--error" style="margin-bottom: 16px;">
                        <strong><?php echo esc_html($test_result['message']); ?></strong>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <form method="post" action="">
                <?php wp_nonce_field('wpdm_test_connection', 'wpdm_test_nonce'); ?>
                <?php submit_button('Probar conexión', 'secondary', 'wpdm_test_connection', false); ?>
            </form>
        </div>
    </div>
</div>
