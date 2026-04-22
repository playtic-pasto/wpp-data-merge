<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Admin\PostType\Controllers;

use WPDM\Core\Domain\Projects\ProjectsRepository;
use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Core\Infrastructure\WordPress\Admin\Support\SyncUrlBuilder;
use WPDM\Core\Infrastructure\WordPress\Cron\CronHistory;
use WPDM\Core\Infrastructure\WordPress\Cron\CronSettings;
use WPDM\Core\Infrastructure\WordPress\Cron\ProjectLock;
use WPDM\Shared\Logger\WPDM_Logger;
use WPDM\Shared\Helpers\UserHelper;

/**
 * Controlador que atiende la acción admin-post de sincronización y muestra
 * notices tras la operación.
 *
 * @name SyncController
 * @package WPDM\Core\Infrastructure\WordPress\Admin\PostType\Controllers
 * @since 1.0.0
 */
class SyncController
{
    private const NOTICE_PREFIX = 'wpdm_project_sync_notice_';
    private const COOLDOWN_PREFIX = 'wpdm_project_sync_cooldown_';

    private ProjectSyncService $service;
    private WPDM_Logger $logger;
    private CronHistory $history;
    private array $config;

    public function __construct(?ProjectSyncService $service = null, ?WPDM_Logger $logger = null, ?CronHistory $history = null)
    {
        $this->service = $service ?? new ProjectSyncService();
        $this->logger = $logger ?? new WPDM_Logger(WPDM_PATH);
        $this->config = require WPDM_PATH . 'config/cron.php';
        $settings = new CronSettings();
        $this->history = $history ?? new CronHistory($settings);
    }

    public function register(): void
    {
        \add_action('admin_post_' . SyncUrlBuilder::ACTION, [$this, 'handle']);
        \add_action('admin_notices', [$this, 'maybeShowNotice']);
    }

    public function handle(): void
    {
        if (!\current_user_can('edit_posts')) {
            \wp_die('No autorizado.');
        }

        $postId = (int) ($_GET['post'] ?? 0);
        $redir  = isset($_GET['redirect']) ? \esc_url_raw((string) \wp_unslash($_GET['redirect'])) : '';

        if ($postId <= 0) {
            \wp_die('Post inválido.');
        }

        \check_admin_referer(SyncUrlBuilder::nonceAction($postId));

        $postTitle = \get_the_title($postId) ?: "Post #{$postId}";
        $userLabel = UserHelper::getCurrentUserLabel();

        // Verificar cooldown para evitar spam del botón
        if (!$this->checkCooldown($postId)) {
            $cooldownSeconds = (int) $this->config['project_sync_cooldown'];
            $this->logger->info("Sync manual: omitido por cooldown para proyecto '{$postTitle}' (ID: {$postId})");
            \set_transient($this->noticeKey(), [
                'type'    => 'warning',
                'message' => \sprintf('Por favor espera %d segundos antes de sincronizar nuevamente.', $cooldownSeconds),
            ], 30);
            if ($redir === '') {
                $redir = \admin_url('edit.php?post_type=' . ProjectsRepository::POST_TYPE);
            }
            \wp_safe_redirect($redir);
            exit;
        }

        // Adquirir lock atómico a nivel de proyecto
        $lockTtl = (int) ($this->config['project_lock_ttl'] ?? 120);
        $lock = new ProjectLock($postId, $lockTtl);

        if (!$lock->acquire()) {
            $this->logger->warning("Sync manual: omitido para proyecto '{$postTitle}' (ID: {$postId}) - otra sincronización en curso.");
            \set_transient($this->noticeKey(), [
                'type'    => 'warning',
                'message' => 'Sincronización ya en curso para este proyecto. Por favor espera.',
            ], 30);
            if ($redir === '') {
                $redir = \admin_url('edit.php?post_type=' . ProjectsRepository::POST_TYPE);
            }
            \wp_safe_redirect($redir);
            exit;
        }

        $this->logger->info("Sync manual: iniciando para proyecto '{$postTitle}' (ID: {$postId}) por {$userLabel}");

        $start = \microtime(true);
        $startTs = \time();
        $result = ['success' => false, 'message' => ''];

        try {
            $result = $this->service->syncPost($postId);

            if ($result['success']) {
                $this->logger->info("Sync manual: completado para proyecto '{$postTitle}' (ID: {$postId}) - {$result['message']}");
            } else {
                $this->logger->error("Sync manual: error en proyecto '{$postTitle}' (ID: {$postId}) - {$result['message']}");
            }

            \set_transient($this->noticeKey(), [
                'type'    => $result['success'] ? 'success' : 'error',
                'message' => $result['message'],
            ], 30);
        } finally {
            $elapsed = \microtime(true) - $start;
            $lock->release();

            // Registrar en historial
            $this->history->add(
                $startTs,
                'project',
                $result['success'] ? 'success' : 'error',
                $elapsed,
                $result['aggregates'] ?? [],
                [
                    'post_id' => $postId,
                    'title'   => $postTitle,
                    'user'    => $userLabel,
                ]
            );
        }

        if ($redir === '') {
            $redir = \admin_url('edit.php?post_type=' . ProjectsRepository::POST_TYPE);
        }
        \wp_safe_redirect($redir);
        exit;
    }

    public function maybeShowNotice(): void
    {
        $key    = $this->noticeKey();
        $notice = \get_transient($key);
        if (!\is_array($notice)) return;

        \delete_transient($key);

        $cls = ($notice['type'] ?? '') === 'success' ? 'notice-success' : 'notice-error';
        echo '<div class="notice ' . \esc_attr($cls) . ' is-dismissible"><p>'
            . \esc_html($notice['message'] ?? '')
            . '</p></div>';
    }

    private function noticeKey(): string
    {
        return self::NOTICE_PREFIX . \get_current_user_id();
    }

    /**
     * Verifica cooldown por proyecto. Si no hay cooldown activo, lo setea.
     * Retorna true si se puede proceder, false si debe esperar.
     */
    private function checkCooldown(int $postId): bool
    {
        $key = self::COOLDOWN_PREFIX . $postId;
        if (\get_transient($key)) {
            return false;
        }
        $ttl = (int) ($this->config['project_sync_cooldown'] ?? 60);
        \set_transient($key, \time(), $ttl);
        return true;
    }
}
