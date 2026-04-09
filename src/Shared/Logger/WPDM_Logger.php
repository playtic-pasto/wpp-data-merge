<?php

declare(strict_types=1);

namespace WPDM\Shared\Logger;

/**
 * Permite registrar mensajes de log en un archivo específico para el plugin WP Data Merge, facilitando la depuración y el seguimiento de eventos importantes durante la ejecución del plugin.
 * 
 * @name WPDM_Logger
 * @package WPDM\Shared\Logger
 * @since 1.0.0
 */
class WPDM_Logger
{

    private string $file;

    /**
     * Inicializa el logger estableciendo la ruta del archivo de log donde se registrarán los mensajes, asegurándose de que el directorio de logs exista o creándolo si es necesario.
     * @param string $basePath La ruta base del plugin donde se encuentra el directorio de logs, utilizada para construir la ruta completa del archivo de log donde se registrarán los mensajes del plugin WP Data Merge.
     */
    public function __construct(string $basePath)
    {
        $this->file = $basePath . 'logs/wpdm.log';
        
        if (!file_exists($basePath . 'logs')) {
            mkdir($basePath . 'logs', 0755, true);
        }
    }

    /**
     * Registra un mensaje de información en el archivo de log.
     * @param string $message El mensaje a registrar.
     * @return void
     */

    public function info(string $message): void
    {
        $this->write('INFO', $message);
    }

    public function warning(string $message): void
    {
        $this->write('WARNING', $message);
    }

    public function error(string $message): void
    {
        $this->write('ERROR', $message);
    }

    /**
     * Escribe un mensaje en el archivo de log con el nivel y la marca de tiempo especificados.
     * @param string $level El nivel del mensaje (INFO, ERROR, etc.).
     * @param string $message El mensaje a registrar.
     * @return void
     */
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
