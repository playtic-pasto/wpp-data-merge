<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Cron;

/**
 * Expone la configuración del Cron Job leyendo/escribiendo opciones de
 * WordPress y aplicando defaults desde config/cron.php.
 *
 * @name CronSettings
 * @package WPDM\Core\Infrastructure\WordPress\Cron
 * @since 1.0.0
 */
class CronSettings
{
    /** @var array<string, mixed> */
    private array $config;

    public function __construct()
    {
        $this->config = require WPDM_PATH . 'config/cron.php';
    }

    public function config(): array
    {
        return $this->config;
    }

    public function hook(): string
    {
        return (string) $this->config['hook'];
    }

    public function scheduleId(): string
    {
        return (string) $this->config['schedule_id'];
    }

    public function isEnabled(): bool
    {
        $value = \get_option($this->config['option_enabled'], null);
        return $value === '1' || $value === 1 || $value === true;
    }

    public function setEnabled(bool $enabled): void
    {
        \update_option($this->config['option_enabled'], $enabled ? '1' : '0');
    }

    public function intervalMinutes(): int
    {
        $value = (int) \get_option($this->config['option_interval'], (int) $this->config['default_interval_minutes']);
        return \max($value, 1);
    }

    public function setIntervalMinutes(int $minutes): void
    {
        $minutes = \max($minutes, 1);
        \update_option($this->config['option_interval'], $minutes);
    }

    public function intervalSeconds(): int
    {
        return $this->intervalMinutes() * 60;
    }

    public function lockTtl(): int
    {
        return (int) $this->config['lock_ttl'];
    }

    public function historySize(): int
    {
        return (int) $this->config['history_size'];
    }

    public function optionLastRun(): string
    {
        return (string) $this->config['option_last_run'];
    }

    public function optionLastError(): string
    {
        return (string) $this->config['option_last_error'];
    }

    public function optionHistory(): string
    {
        return (string) $this->config['option_history'];
    }

    public function optionLock(): string
    {
        return (string) $this->config['option_lock'];
    }

    public function cooldownKey(string $type): string
    {
        $map = [
            'auto'   => $this->config['cooldown_auto'],
            'manual' => $this->config['cooldown_manual'],
            'api'    => $this->config['cooldown_api'],
        ];
        return (string) ($map[$type] ?? $this->config['cooldown_auto']);
    }
}
