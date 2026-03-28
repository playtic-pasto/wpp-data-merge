<?php

namespace WPDM\Shared\Logger;

class WPDM_Logger
{

    private string $file;

    public function __construct(string $basePath)
    {
        $this->file = $basePath . 'logs/wpdm.log';
        
        if (!file_exists($basePath . 'logs')) {
            mkdir($basePath . 'logs', 0755, true);
        }
    }

    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    private function write(string $level, string $message): void
    {
        $now = new \DateTimeImmutable('now', wp_timezone());
        $timestamp = $now->format('Y-m-d H:i:s P');

        file_put_contents(
            $this->file,
            "[WPDM] [$timestamp] [$level] $message\n",
            FILE_APPEND
        );
    }
}
