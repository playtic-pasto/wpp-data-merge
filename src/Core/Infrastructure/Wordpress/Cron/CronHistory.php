<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\WordPress\Cron;

/**
 * Historial de las últimas N ejecuciones del cron, persistido en una option.
 *
 * @name CronHistory
 * @package WPDM\Core\Infrastructure\WordPress\Cron
 * @since 1.0.0
 */
class CronHistory
{
    public function __construct(private CronSettings $settings) {}

    /**
     * @param 'auto'|'manual'|'api' $type
     * @param 'success'|'error' $status
     * @param array<string, mixed> $stats
     */
    public function add(int $timestamp, string $type, string $status, float $durationSeconds, array $stats = []): void
    {
        $history = $this->all();
        $history[] = [
            'timestamp' => $timestamp,
            'type'      => $type,
            'status'    => $status,
            'duration'  => \round($durationSeconds, 2),
            'stats'     => $stats,
        ];

        $max = $this->settings->historySize();
        if (\count($history) > $max) {
            $history = \array_slice($history, -$max);
        }

        \update_option($this->settings->optionHistory(), $history, false);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function all(): array
    {
        $raw = \get_option($this->settings->optionHistory(), []);
        return \is_array($raw) ? $raw : [];
    }

    public function clear(): void
    {
        \delete_option($this->settings->optionHistory());
    }
}
