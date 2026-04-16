<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Cron;

use WPDM\Core\Domain\Sync\ProjectSyncService;
use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Orquesta la ejecución del cron desde cualquiera de sus 3 entry points
 * (auto/manual/api): aplica cooldown, adquiere lock atómico, invoca la lógica
 * de sincronización (ProjectSyncService::syncAllActive) y registra resultado
 * en historial, last_run y last_error.
 *
 * @name CronRunner
 * @package WPDM\Core\Infrastructure\WordPress\Cron
 * @since 1.0.0
 */
class CronRunner
{
    public function __construct(
        private CronSettings $settings,
        private CronLock $lock,
        private CronHistory $history,
        private ProjectSyncService $syncService,
        private WPDM_Logger $logger,
    ) {}

    public function runAuto(): bool
    {
        $cooldownSeconds = (int) \floor($this->settings->intervalSeconds() / 2);
        if (!$this->checkCooldown('auto', $cooldownSeconds)) {
            return false;
        }
        return $this->run('auto');
    }

    public function runManual(): bool
    {
        if (!$this->checkCooldown('manual', 5 * \MINUTE_IN_SECONDS)) {
            return false;
        }
        return $this->run('manual');
    }

    /**
     * @return array{executed:bool, success:bool, message?:string}
     */
    public function runApi(): array
    {
        if (!$this->checkCooldown('api', 5 * \MINUTE_IN_SECONDS)) {
            return ['executed' => false, 'success' => false, 'message' => 'Cooldown activo.'];
        }
        $success = $this->run('api');
        return ['executed' => true, 'success' => $success];
    }

    /**
     * Núcleo de ejecución compartido por los 3 entry points.
     *
     * @param 'auto'|'manual'|'api' $type
     */
    private function run(string $type): bool
    {
        if (!$this->lock->acquire()) {
            $this->logger->warning("Cron: ejecución {$type} omitida (otra en curso).");
            return false;
        }

        $start     = \microtime(true);
        $startTs   = \time();
        $success   = false;
        $stats     = ['processed' => 0, 'succeeded' => 0, 'failed' => 0, 'skipped' => 0];
        $errorMsg  = '';

        $this->logger->info("Cron: iniciando ejecución {$type}.");

        try {
            $report = $this->syncService->syncAllActive();
            $stats = [
                'processed' => (int) $report['processed'],
                'succeeded' => (int) $report['succeeded'],
                'failed'    => (int) $report['failed'],
                'skipped'   => (int) $report['skipped'],
            ];

            \update_option($this->settings->optionLastRun(), $startTs);

            if ($stats['failed'] > 0) {
                $errorMsg = \sprintf('%d proyecto(s) con error.', $stats['failed']);
                \update_option($this->settings->optionLastError(), $errorMsg);
                $success = true; // parcialmente OK, no consideramos error catastrófico
            } else {
                \delete_option($this->settings->optionLastError());
                $success = true;
            }
        } catch (\Throwable $e) {
            $errorMsg = $e->getMessage();
            $this->logger->error('Cron: error crítico — ' . $errorMsg);
            \update_option($this->settings->optionLastError(), $errorMsg);
            $success = false;
        } finally {
            $elapsed = \microtime(true) - $start;
            $this->history->add($startTs, $type, $success ? 'success' : 'error', $elapsed, $stats);
            $this->lock->release();

            $this->logger->info(\sprintf(
                'Cron: finalizado tipo=%s duración=%.2fs stats=%s',
                $type,
                $elapsed,
                \wp_json_encode($stats)
            ));
        }

        return $success;
    }

    /**
     * Verifica cooldown por tipo. Si no hay cooldown activo, lo setea.
     */
    private function checkCooldown(string $type, int $ttlSeconds): bool
    {
        $key = $this->settings->cooldownKey($type);
        if (\get_transient($key)) {
            $this->logger->info("Cron {$type}: omitido por cooldown.");
            return false;
        }
        \set_transient($key, \time(), $ttlSeconds);
        return true;
    }
}
