<?php
if (!defined('ABSPATH')) exit;
?>
<div class="wrap wpdm-wrap">
    <h1>Ajustes de Conexión API</h1>
    <p>Configura las credenciales para conectar con la API del ERP SINCO.</p>

    <div class="wpdm-section">
        <h2>Credenciales de autenticación</h2>
        <form method="post" action="">
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
                            value="https://sinco.constructoramelendez.com/SincoConsMelendez_Nueva/V3/API/Auth/Usuario"
                            class="regular-text"
                            placeholder="https://ejemplo.com/api"
                        />
                        <p class="description">URL base del endpoint de autenticación de SINCO.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpdm_api_key">Clave de API</label>
                    </th>
                    <td>
                        <input
                            type="password"
                            id="wpdm_api_key"
                            name="wpdm_api_key"
                            value=""
                            class="regular-text"
                            placeholder="Ingrese la clave de API"
                        />
                        <p class="description">Clave secreta proporcionada por el administrador del ERP.</p>
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
                            placeholder="Nombre de usuario"
                        />
                        <p class="description">Usuario de acceso a la API (si aplica).</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="wpdm_api_password">Contraseña</label>
                    </th>
                    <td>
                        <input
                            type="password"
                            id="wpdm_api_password"
                            name="wpdm_api_password"
                            value=""
                            class="regular-text"
                            placeholder="Contraseña"
                        />
                        <p class="description">Contraseña de acceso a la API (si aplica).</p>
                    </td>
                </tr>
            </table>

            <?php submit_button('Guardar ajustes', 'primary', 'wpdm_save_settings', true, ['disabled' => 'disabled']); ?>
            <p class="description">El guardado de ajustes estará disponible próximamente.</p>
        </form>
    </div>

    <div class="wpdm-section">
        <h2>Probar conexión</h2>
        <p>Verifica que las credenciales sean correctas realizando una conexión de prueba.</p>
        <button type="button" class="button button-secondary" disabled>Probar conexión</button>
        <p class="description">Esta funcionalidad estará disponible una vez se guarden los ajustes.</p>
    </div>
</div>
