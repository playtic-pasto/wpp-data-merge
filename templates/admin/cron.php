<?php
if (!defined('ABSPATH')) exit;

use WPDM\Core\Infrastructure\WordPress\Admin\Support\Formatter;

/** @var \WPDM\Core\Infrastructure\WordPress\Cron\CronSettings $settings */
/** @var int|null $nextRun */
/** @var int $lastRun */
/** @var string $lastError */
/** @var list<array<string, mixed>> $history */
/** @var string $token */
/** @var string $endpoint */
/** @var list<array{project:array<string,mixed>, error:string}> $errors */
/** @var array<string, string> $actions */

$serverTime = current_time('Y-m-d H:i:s');
$timezone   = wp_timezone_string();
$enabled    = $settings->isEnabled();
$minutes    = $settings->intervalMinutes();
?>
<div class="wrap wpdm-wrap">
    <h1>Cron Job — Sincronización Automática</h1>
    <p>Gestiona la tarea programada que sincroniza todos los proyectos desde SINCO.</p>

    <!-- Configuración -->
    <div class="wpdm-section">
        <h2>Configuración</h2>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <input type="hidden" name="action" value="<?php echo esc_attr($actions['save']); ?>" />
            <?php wp_nonce_field($actions['save']); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">Estado</th>
                    <td>
                        <label>
                            <input type="checkbox" name="wpdm_cron_enabled" value="1" <?php checked($enabled); ?> />
                            Activar sincronización automática
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="wpdm_cron_interval_minutes">Intervalo (minutos)</label></th>
                    <td>
                        <input type="number" min="1" max="10080" step="1"
                               id="wpdm_cron_interval_minutes"
                               name="wpdm_cron_interval_minutes"
                               value="<?php echo esc_attr((string) $minutes); ?>"
                               class="small-text" /> minutos
                        <p class="description">Frecuencia con la que se ejecutará el cron (mínimo 1 minuto).</p>
                    </td>
                </tr>
            </table>
            <?php submit_button('Guardar configuración'); ?>
        </form>
    </div>

    <!-- Estado actual -->
    <div class="wpdm-section">
        <h2>Estado actual</h2>
        <table class="widefat">
            <tbody>
                <tr>
                    <th style="width:240px;">Estado</th>
                    <td>
                        <?php if ($enabled) : ?>
                            <span style="display:inline-block;padding:2px 10px;border-radius:10px;background:#d1fae5;color:#065f46;font-weight:600;font-size:11px;">Activo</span>
                        <?php else : ?>
                            <span style="display:inline-block;padding:2px 10px;border-radius:10px;background:#e5e7eb;color:#374151;font-weight:600;font-size:11px;">Inactivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Última ejecución</th>
                    <td><?php echo esc_html(Formatter::timestamp($lastRun ?: null)); ?></td>
                </tr>
                <tr>
                    <th>Próxima ejecución</th>
                    <td><?php echo esc_html(Formatter::timestamp($nextRun)); ?></td>
                </tr>
                <tr>
                    <th>Intervalo configurado</th>
                    <td><?php echo esc_html(sprintf('Cada %d minutos', $minutes)); ?></td>
                </tr>
                <tr>
                    <th>Hora del servidor</th>
                    <td><?php echo esc_html($serverTime); ?> (<?php echo esc_html($timezone); ?>)</td>
                </tr>
                <?php if ($lastError !== '') : ?>
                    <tr>
                        <th>Último error</th>
                        <td style="color:#991b1b;"><?php echo esc_html($lastError); ?></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p style="margin-top:15px;">
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                <input type="hidden" name="action" value="<?php echo esc_attr($actions['run']); ?>" />
                <?php wp_nonce_field($actions['run']); ?>
                <?php submit_button('Ejecutar ahora', 'primary', 'submit', false); ?>
            </form>
        </p>
    </div>

    <!-- Endpoint REST -->
    <div class="wpdm-section">
        <h2>Endpoint REST (cron del sistema)</h2>
        <p>Úsalo desde un cron externo (wget/curl) cuando WP-Cron no sea fiable.</p>
        <table class="widefat">
            <tbody>
                <tr>
                    <th style="width:240px;">URL</th>
                    <td><code><?php echo esc_html($endpoint); ?></code></td>
                </tr>
                <tr>
                    <th>Autenticación</th>
                    <td>
                        Header <code>X-WPDM-Token: &lt;token&gt;</code> o query <code>?token=&lt;token&gt;</code>
                    </td>
                </tr>
                <tr>
                    <th>Token actual</th>
                    <td>
                        <?php if ($token !== '') : ?>
                            <code style="user-select:all;"><?php echo esc_html($token); ?></code>
                        <?php else : ?>
                            <em style="color:#b45309;">Aún no generado.</em>
                        <?php endif; ?>

                        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;margin-left:10px;">
                            <input type="hidden" name="action" value="<?php echo esc_attr($actions['token']); ?>" />
                            <?php wp_nonce_field($actions['token']); ?>
                            <button type="submit" class="button"
                                    onclick="return confirm('Esto invalidará el token actual. ¿Continuar?');">
                                <?php echo $token === '' ? 'Generar token' : 'Regenerar'; ?>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php if ($token !== '') : ?>
                    <tr>
                        <th>Ejemplo cURL</th>
                        <td><code>curl -H "X-WPDM-Token: <?php echo esc_html($token); ?>" <?php echo esc_html($endpoint); ?></code></td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Historial -->
    <div class="wpdm-section">
        <h2 style="display:flex;justify-content:space-between;align-items:center;">
            Historial de ejecuciones
            <?php if (!empty($history)) : ?>
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin:0;">
                    <input type="hidden" name="action" value="<?php echo esc_attr($actions['clear']); ?>" />
                    <?php wp_nonce_field($actions['clear']); ?>
                    <button type="submit" class="button button-link-delete"
                            onclick="return confirm('¿Limpiar historial?');">Limpiar</button>
                </form>
            <?php endif; ?>
        </h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Duración</th>
                    <th>Procesados</th>
                    <th>OK / Error / Pausados</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($history)) : ?>
                    <tr><td colspan="6" style="text-align:center;padding:20px;color:#646970;">No hay ejecuciones registradas.</td></tr>
                <?php else : ?>
                    <?php foreach (array_reverse($history) as $row) :
                        $stats = $row['stats'] ?? [];
                        $ok = $row['status'] === 'success';
                    ?>
                        <tr>
                            <td><?php echo esc_html(Formatter::timestamp((int) $row['timestamp'])); ?></td>
                            <td><?php echo esc_html((string) ($row['type'] ?? '—')); ?></td>
                            <td style="color:<?php echo $ok ? '#065f46' : '#991b1b'; ?>;font-weight:600;">
                                <?php echo $ok ? 'success' : 'error'; ?>
                            </td>
                            <td><?php echo esc_html(sprintf('%.2f s', (float) ($row['duration'] ?? 0))); ?></td>
                            <td><?php echo esc_html((string) (int) ($stats['processed'] ?? 0)); ?></td>
                            <td>
                                <?php echo esc_html(sprintf(
                                    '%d / %d / %d',
                                    (int) ($stats['succeeded'] ?? 0),
                                    (int) ($stats['failed']    ?? 0),
                                    (int) ($stats['skipped']   ?? 0)
                                )); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Errores por proyecto -->
    <div class="wpdm-section">
        <h2>Errores recientes por proyecto</h2>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th>Proyecto</th>
                    <th>Error</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($errors)) : ?>
                    <tr><td colspan="2" style="text-align:center;padding:20px;color:#646970;">Sin errores registrados.</td></tr>
                <?php else : ?>
                    <?php foreach ($errors as $e) : ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(get_edit_post_link($e['project']['post_id'])); ?>">
                                    <?php echo esc_html($e['project']['title']); ?>
                                </a>
                            </td>
                            <td style="color:#991b1b;"><?php echo esc_html($e['error']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
