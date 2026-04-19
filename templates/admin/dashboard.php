<?php
if (!defined('ABSPATH')) exit;

/** @var array<string, mixed> $data */
?>
<div class="wrap wpdm-wrap">
    <h1>WP Data Merge</h1>
    <p class="wpdm-subtitle">Sincronización de datos desde el ERP SINCO hacia WordPress</p>

    <!-- Estadísticas principales -->
    <div class="wpdm-stats">
        <div class="wpdm-stat">
            <div class="wpdm-stat-icon wpdm-stat-icon--blue">
                <span class="dashicons dashicons-portfolio"></span>
            </div>
            <div class="wpdm-stat-content">
                <span class="wpdm-stat-value"><?php echo esc_html((string) $data['configured']); ?></span>
                <span class="wpdm-stat-label">Configurados</span>
            </div>
        </div>

        <div class="wpdm-stat">
            <div class="wpdm-stat-icon wpdm-stat-icon--green">
                <span class="dashicons dashicons-yes-alt"></span>
            </div>
            <div class="wpdm-stat-content">
                <span class="wpdm-stat-value"><?php echo esc_html((string) $data['synced']); ?></span>
                <span class="wpdm-stat-label">Sincronizados</span>
            </div>
        </div>

        <div class="wpdm-stat">
            <div class="wpdm-stat-icon wpdm-stat-icon--red">
                <span class="dashicons dashicons-warning"></span>
            </div>
            <div class="wpdm-stat-content">
                <span class="wpdm-stat-value"><?php echo esc_html((string) $data['with_errors']); ?></span>
                <span class="wpdm-stat-label">Con errores</span>
            </div>
        </div>

        <div class="wpdm-stat">
            <div class="wpdm-stat-icon wpdm-stat-icon--yellow">
                <span class="dashicons dashicons-clock"></span>
            </div>
            <div class="wpdm-stat-content">
                <?php if ($data['last_sync_date']): ?>
                    <span class="wpdm-stat-value wpdm-stat-value--small"><?php echo esc_html($data['last_sync_date']); ?></span>
                <?php else: ?>
                    <span class="wpdm-stat-value wpdm-stat-value--small">—</span>
                <?php endif; ?>
                <span class="wpdm-stat-label">Última sync</span>
            </div>
        </div>
    </div>

    <div class="wpdm-cards">
        <!-- Estado del sistema -->
        <div class="wpdm-card">
            <h2><span class="dashicons dashicons-admin-settings"></span> Estado del sistema</h2>
            <table class="wpdm-status-table">
                <tr>
                    <th>Plugin</th>
                    <td>v<?php echo esc_html(WPDM_VERSION); ?></td>
                </tr>
                <tr>
                    <th>PHP / WordPress</th>
                    <td><?php echo esc_html(phpversion()); ?> / <?php echo esc_html(get_bloginfo('version')); ?></td>
                </tr>
                <tr>
                    <th>Conexión API</th>
                    <td>
                        <?php if ($data['api_configured']): ?>
                            <span class="wpdm-badge wpdm-badge--active">Configurada</span>
                        <?php else: ?>
                            <span class="wpdm-badge wpdm-badge--pending">Sin configurar</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Cron Job</th>
                    <td>
                        <?php if ($data['cron_enabled']): ?>
                            <span class="wpdm-badge wpdm-badge--active">Cada <?php echo esc_html((string) $data['cron_interval']); ?> min</span>
                        <?php else: ?>
                            <span class="wpdm-badge wpdm-badge--inactive">Inactivo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr>
                    <th>Proyectos</th>
                    <td><?php echo esc_html((string) $data['total_projects']); ?> total, <?php echo esc_html((string) $data['configured']); ?> con IDs</td>
                </tr>
            </table>

            <?php if ($data['cron_last_error']): ?>
                <div class="wpdm-alert wpdm-alert--error">
                    <strong>Último error del cron:</strong> <?php echo esc_html((string) $data['cron_last_error']); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Historial de sincronizaciones -->
        <div class="wpdm-card">
            <h2><span class="dashicons dashicons-backup"></span> Últimas sincronizaciones</h2>
            <?php if (empty($data['history'])): ?>
                <div class="wpdm-empty">
                    <span class="dashicons dashicons-update"></span>
                    <p>Aún no se han realizado sincronizaciones.</p>
                </div>
            <?php else: ?>
                <table class="wpdm-history-table">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Tiempo</th>
                            <th>Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($data['history'] as $entry):
                            $stats = $entry['stats'] ?? [];
                        ?>
                            <tr>
                                <td><?php echo esc_html(wp_date('d/m/Y H:i', (int) $entry['timestamp'])); ?></td>
                                <td><?php echo esc_html(ucfirst((string) $entry['type'])); ?></td>
                                <td>
                                    <?php if ($entry['status'] === 'success'): ?>
                                        <span class="wpdm-badge wpdm-badge--active">OK</span>
                                    <?php else: ?>
                                        <span class="wpdm-badge wpdm-badge--inactive">Error</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html(number_format((float) $entry['duration'], 1)); ?>s</td>
                                <td>
                                    <span class="wpdm-history-stats">
                                        <span class="ok"><?php echo esc_html((string) (int) ($stats['succeeded'] ?? 0)); ?> ok</span>
                                        <span class="err"><?php echo esc_html((string) (int) ($stats['failed'] ?? 0)); ?> err</span>
                                        <span class="skip"><?php echo esc_html((string) (int) ($stats['skipped'] ?? 0)); ?> skip</span>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
