<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Cron;

use WPDM\Shared\Logger\WPDM_Logger;

/**
 * Gestiona la programación del cron: registra un intervalo dinámico basado en
 * configuración, programa/limpia el evento y hace auto-reparación si se
 * desprograma o queda atrasado.
 *
 * @name CronScheduler
 * @package WPDM\Core\Infrastructure\WordPress\Cron
 * @since 1.0.0
 */
class CronScheduler
{
    public function __construct(
        private CronSettings $settings,
        private CronRunner $runner,
        private WPDM_Logger $logger,
    ) {}

    public function register(): void
    {
        \add_filter('cron_schedules', [$this, 'registerCustomSchedule']);
        \add_action($this->settings->hook(), [$this->runner, 'runAuto']);
        \add_action('init', [$this, 'checkAndFixSchedule']);
    }

    /**
     * Registra el intervalo custom dinámico en el filtro cron_schedules de WP.
     *
     * @param array<string, array{interval:int, display:string}> $schedules
     * @return array<string, array{interval:int, display:string}>
     */
    public function registerCustomSchedule(array $schedules): array
    {
        $id = $this->settings->scheduleId();
        $minutes = $this->settings->intervalMinutes();

        $schedules[$id] = [
            'interval' => $minutes * 60,
            'display'  => \sprintf('Cada %d minutos (WPDM)', $minutes),
        ];

        return $schedules;
    }

    /**
     * Programa el evento si no existe. Si ya existe con otro intervalo, lo reemplaza.
     */
    public function schedule(): void
    {
        $hook = $this->settings->hook();
        $id   = $this->settings->scheduleId();
        $seconds = $this->settings->intervalSeconds();

        $existing = \wp_next_scheduled($hook);
        if ($existing) {
            \wp_unschedule_event($existing, $hook);
            \wp_clear_scheduled_hook($hook);
        }

        $scheduled = \wp_schedule_event(\time() + $seconds, $id, $hook);

        if ($scheduled !== false) {
            $this->logger->info(\sprintf('Cron programado: %s (cada %d min)', $hook, $this->settings->intervalMinutes()));
        } else {
            $this->logger->error("Cron: no se pudo programar {$hook}");
        }
    }

    /**
     * Reprograma forzadamente.
     */
    public function reschedule(): void
    {
        $hook = $this->settings->hook();
        while ($timestamp = \wp_next_scheduled($hook)) {
            \wp_unschedule_event($timestamp, $hook);
        }
        \wp_schedule_event(\time() + $this->settings->intervalSeconds(), $this->settings->scheduleId(), $hook);
        $this->logger->info('Cron reprogramado.');
        $this->logger->info('-----------');
    }

    /**
     * Elimina todas las programaciones del evento.
     */
    public function clear(): void
    {
        $hook = $this->settings->hook();
        while ($timestamp = \wp_next_scheduled($hook)) {
            \wp_unschedule_event($timestamp, $hook);
        }
        \wp_clear_scheduled_hook($hook);
        $this->logger->info("Cron eliminado: {$hook}");
    }

    /**
     * Auto-reparación: si el cron está habilitado pero no programado, lo
     * programa. Si está atrasado, lo ejecuta y reprograma. Se protege por
     * transient para evitar loops y se salta si la opción está desactivada.
     */
    public function checkAndFixSchedule(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        if (!$this->settings->isEnabled()) {
            return;
        }

        $hook = $this->settings->hook();
        $next = \wp_next_scheduled($hook);

        if (!$next) {
            $this->logger->info('Cron: no programado, creando schedule.');
            $this->schedule();
            return;
        }

        if (\get_transient('wpdm_last_schedule_check') && $next > \time()) {
            return;
        }
        \set_transient('wpdm_last_schedule_check', \time(), \HOUR_IN_SECONDS);

        if ($next < \time()) {
            $this->logger->info(\sprintf('Cron atrasado %d minutos, ejecutando.', \round((\time() - $next) / 60)));
            $this->runner->runAuto();

            $nextAfter = \wp_next_scheduled($hook);
            if (!$nextAfter || $nextAfter < \time()) {
                $this->reschedule();
            }
        }
    }

    public function nextRunTimestamp(): ?int
    {
        $ts = \wp_next_scheduled($this->settings->hook());
        return $ts ? (int) $ts : null;
    }
}
