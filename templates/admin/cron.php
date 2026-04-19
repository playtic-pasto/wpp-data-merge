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
    <h1>Cron Job</h1>
    <p class="wpdm-subtitle">Sincronización automática de proyectos desde SINCO</p>

    <!-- Stats rápidas -->
    <div class="wpdm-stats">
        <div class="wpdm-stat">
            <div class="wpdm-stat-icon <?php echo $enabled ? 'wpdm-stat-icon--green' : 'wpdm-stat-icon--red'; ?>">
                <span class="dashicons <?php echo $enabled ? 'dashicons-yes-alt' : 'dashicons-dismiss'; ?>"></span>
            </div>
            <div class="wpdm-stat-content">
                <span class="wpdm-stat-value wpdm-stat-value--small"><?php echo $enabled ? 'Activo' : 'Inactivo'; ?></span>
                <span class="wpdm-stat-label">Estado</span>
            </div>
        </div>
        <div class="wpdm-stat">
            <div class="wpdm-stat-icon wpdm-stat-icon--blue">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="wpdm-stat-content">
                <span class="wpdm-stat-value wpdm-stat-value--small"><?php echo esc_html(Formatter::timestamp($lastRun ?: null)); ?></span>
                <span class="wpdm-stat-label">Última ejecución</span>
            </div>
        </div>
        <div class="wpdm-stat">
            <div class="wpdm-stat-icon wpdm-stat-icon--yellow">
                <span class="dashicons dashicons-calendar-alt"></span>
            </div>
            <div class="wpdm-stat-content">
                <span class="wpdm-stat-value wpdm-stat-value--small"><?php echo esc_html(Formatter::timestamp($nextRun)); ?></span>
                <span class="wpdm-stat-label">Próxima ejecución</span>
            </div>
        </div>
        <div class="wpdm-stat">
            <div class="wpdm-stat-icon wpdm-stat-icon--blue">
                <span class="dashicons dashicons-update"></span>
            </div>
            <div class="wpdm-stat-content">
                <span class="wpdm-stat-value"><?php echo esc_html((string) $minutes); ?></span>
                <span class="wpdm-stat-label">Intervalo (min)</span>
            </div>
        </div>
    </div>

    <div class="wpdm-cards">
        <!-- Configuración -->
        <div class="wpdm-card">
            <h2><span class="dashicons dashicons-admin-settings"></span> Configuración</h2>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="<?php echo esc_attr($actions['save']); ?>" />
                <?php wp_nonce_field($actions['save']); ?>

                <table class="wpdm-status-table">
                    <tr>
                        <th>Activar</th>
                        <td>
                            <label>
                                <input type="checkbox" name="wpdm_cron_enabled" value="1" <?php checked($enabled); ?> />
                                Sincronización automática
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="wpdm_cron_interval_minutes">Intervalo</label></th>
                        <td>
                            <input type="number" min="1" max="10080" step="1"
                                   id="wpdm_cron_interval_minutes"
                                   name="wpdm_cron_interval_minutes"
                                   value="<?php echo esc_attr((string) $minutes); ?>"
                                   class="small-text" /> minutos
                        </td>
                    </tr>
                </table>

                <div style="display: flex; gap: 8px; margin-top: 16px;">
                    <?php submit_button('Guardar', 'primary', 'submit', false); ?>
                </div>
            </form>

            <hr style="border: none; border-top: 1px solid #f0f0f1; margin: 20px 0;" />

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                <input type="hidden" name="action" value="<?php echo esc_attr($actions['run']); ?>" />
                <?php wp_nonce_field($actions['run']); ?>
                <?php submit_button('Ejecutar ahora', 'secondary', 'submit', false); ?>
            </form>

            <?php if ($lastError !== ''): ?>
                <div class="wpdm-alert wpdm-alert--error" style="margin-top: 16px;">
                    <strong>Último error:</strong> <?php echo esc_html($lastError); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Endpoint REST -->
        <div class="wpdm-card">
            <h2><span class="dashicons dashicons-rest-api"></span> Endpoint REST</h2>
            <p style="color: #646970; margin-top: 0;">Para cron externo (wget/curl) cuando WP-Cron no sea fiable.</p>

            <table class="wpdm-status-table">
                <tr>
                    <th>URL</th>
                    <td><code style="font-size: 0.85em; word-break: break-all;"><?php echo esc_html($endpoint); ?></code></td>
                </tr>
                <tr>
                    <th>Auth</th>
                    <td><code style="font-size: 0.85em;">X-WPDM-Token: &lt;token&gt;</code></td>
                </tr>
                <tr>
                    <th>Token</th>
                    <td>
                        <?php if ($token !== ''): ?>
                            <code style="font-size: 0.85em; user-select: all;"><?php echo esc_html($token); ?></code>
                        <?php else: ?>
                            <em style="color: #b45309;">No generado</em>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>

            <div style="margin-top: 16px;">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display:inline-block;">
                    <input type="hidden" name="action" value="<?php echo esc_attr($actions['token']); ?>" />
                    <?php wp_nonce_field($actions['token']); ?>
                    <button type="submit" class="button" onclick="return confirm('Esto invalidará el token actual. ¿Continuar?');">
                        <?php echo $token === '' ? 'Generar token' : 'Regenerar token'; ?>
                    </button>
                </form>
            </div>

            <?php if ($token !== ''): ?>
                <div class="wpdm-alert wpdm-alert--info" style="margin-top: 16px;">
                    <strong>cURL:</strong><br />
                    <code style="font-size: 0.8em; word-break: break-all;">curl -H "X-WPDM-Token: <?php echo esc_html($token); ?>" <?php echo esc_html($endpoint); ?></code>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Historial -->
    <div class="wpdm-cards wpdm-cards--full">
        <div class="wpdm-card">
            <h2 style="display: flex; justify-content: space-between; align-items: center;">
                <span><span class="dashicons dashicons-backup"></span> Historial de ejecuciones</span>
                <?php if (!empty($history)): ?>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin: 0;">
                        <input type="hidden" name="action" value="<?php echo esc_attr($actions['clear']); ?>" />
                        <?php wp_nonce_field($actions['clear']); ?>
                        <button type="submit" class="button button-link-delete" style="font-size: 0.85em;"
                                onclick="return confirm('¿Limpiar historial?');">Limpiar</button>
                    </form>
                <?php endif; ?>
            </h2>

            <?php if (empty($history)): ?>
                <div class="wpdm-empty">
                    <span class="dashicons dashicons-update"></span>
                    <p>No hay ejecuciones registradas.</p>
                </div>
            <?php else: ?>
                <table class="wpdm-history-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Duración</th>
                            <th>Procesados</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($history) as $row):
                            $stats = $row['stats'] ?? [];
                        ?>
                            <tr>
                                <td><?php echo esc_html(Formatter::timestamp((int) $row['timestamp'])); ?></td>
                                <td><?php echo esc_html(ucfirst((string) ($row['type'] ?? '—'))); ?></td>
                                <td>
                                    <?php if ($row['status'] === 'success'): ?>
                                        <span class="wpdm-badge wpdm-badge--active">OK</span>
                                    <?php else: ?>
                                        <span class="wpdm-badge wpdm-badge--inactive">Error</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(number_format((float) ($row['duration'] ?? 0), 2)); ?>s</td>
                                <td><?php echo esc_html((string) (int) ($stats['processed'] ?? 0)); ?></td>
                                <td>
                                    <span class="wpdm-history-stats">
                                        <span class="ok"><?php echo (int) ($stats['succeeded'] ?? 0); ?> ok</span>
                                        <span class="err"><?php echo (int) ($stats['failed'] ?? 0); ?> err</span>
                                        <span class="skip"><?php echo (int) ($stats['skipped'] ?? 0); ?> skip</span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Errores por proyecto -->
    <?php if (!empty($errors)): ?>
        <div class="wpdm-cards wpdm-cards--full">
            <div class="wpdm-card">
                <h2><span class="dashicons dashicons-warning"></span> Errores recientes por proyecto</h2>
                <table class="wpdm-history-table">
                    <thead>
                        <tr>
                            <th>Proyecto</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($errors as $e): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($e['project']['post_id'])); ?>">
                                        <?php echo esc_html($e['project']['title']); ?>
                                    </a>
                                </td>
                                <td style="color: #991b1b;"><?php echo esc_html($e['error']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>
