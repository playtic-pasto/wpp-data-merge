<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Controllers;

use WPDM\Core\Infrastructure\WordPress\Cron\CronHistory;
use WPDM\Core\Infrastructure\WordPress\Cron\CronRestController;
use WPDM\Core\Infrastructure\WordPress\Cron\CronRunner;
use WPDM\Core\Infrastructure\WordPress\Cron\CronScheduler;
use WPDM\Core\Infrastructure\WordPress\Cron\CronSettings;

/**
 * Atiende las acciones admin-post de la página Cron Job: guardar ajustes,
 * ejecutar manualmente, regenerar token y limpiar historial.
 *
 * @name CronController
 * @package WPDM\Core\Infrastructure\WordPress\Controllers
 * @since 1.0.0
 */
class CronController
{
    public const ACTION_SAVE     = 'wpdm_cron_save';
    public const ACTION_RUN      = 'wpdm_cron_run';
    public const ACTION_TOKEN    = 'wpdm_cron_token';
    public const ACTION_CLEAR    = 'wpdm_cron_clear';
    private const NOTICE_KEY     = 'wpdm_cron_notice';

    public function __construct(
        private CronSettings $settings,
        private CronScheduler $scheduler,
        private CronRunner $runner,
        private CronHistory $history,
    ) {}

    public function register(): void
    {
        \add_action('admin_post_' . self::ACTION_SAVE,  [$this, 'handleSave']);
        \add_action('admin_post_' . self::ACTION_RUN,   [$this, 'handleRun']);
        \add_action('admin_post_' . self::ACTION_TOKEN, [$this, 'handleToken']);
        \add_action('admin_post_' . self::ACTION_CLEAR, [$this, 'handleClear']);
        \add_action('admin_notices', [$this, 'maybeShowNotice']);
    }

    public function handleSave(): void
    {
        $this->assertAdmin(self::ACTION_SAVE);

        $enabled = isset($_POST['wpdm_cron_enabled']);
        $minutes = (int) ($_POST['wpdm_cron_interval_minutes'] ?? $this->settings->intervalMinutes());

        $wasEnabled = $this->settings->isEnabled();

        $this->settings->setEnabled($enabled);
        $this->settings->setIntervalMinutes($minutes);

        if ($enabled) {
            $this->scheduler->schedule();
        } elseif ($wasEnabled) {
            $this->scheduler->clear();
        }

        $this->storeNotice('success', 'Ajustes del cron actualizados.');
        $this->redirect();
    }

    public function handleRun(): void
    {
        $this->assertAdmin(self::ACTION_RUN);
        $ok = $this->runner->runManual();
        $this->storeNotice($ok ? 'success' : 'warning', $ok
            ? 'Cron ejecutado manualmente.'
            : 'No se pudo ejecutar (cooldown o lock activo).');
        $this->redirect();
    }

    public function handleToken(): void
    {
        $this->assertAdmin(self::ACTION_TOKEN);
        $token = CronRestController::regenerateToken();
        $this->storeNotice('success', 'Nuevo token generado: ' . $token);
        $this->redirect();
    }

    public function handleClear(): void
    {
        $this->assertAdmin(self::ACTION_CLEAR);
        $this->history->clear();
        $this->storeNotice('success', 'Historial limpiado.');
        $this->redirect();
    }

    public function maybeShowNotice(): void
    {
        $key    = self::NOTICE_KEY . '_' . \get_current_user_id();
        $notice = \get_transient($key);
        if (!\is_array($notice)) return;
        \delete_transient($key);

        $cls = match ($notice['type'] ?? '') {
            'success' => 'notice-success',
            'warning' => 'notice-warning',
            default   => 'notice-error',
        };
        echo '<div class="notice ' . \esc_attr($cls) . ' is-dismissible"><p>'
            . \esc_html($notice['message'] ?? '') . '</p></div>';
    }

    private function assertAdmin(string $action): void
    {
        if (!\current_user_can('manage_options')) \wp_die('No autorizado.');
        \check_admin_referer($action);
    }

    private function storeNotice(string $type, string $message): void
    {
        \set_transient(self::NOTICE_KEY . '_' . \get_current_user_id(), ['type' => $type, 'message' => $message], 30);
    }

    private function redirect(): void
    {
        \wp_safe_redirect(\admin_url('admin.php?page=wpdm-cron'));
        exit;
    }
}
