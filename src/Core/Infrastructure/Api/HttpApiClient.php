<?php

declare(strict_types=1);

namespace WPDM\Core\Infrastructure\Api;

/**
 * Cliente HTTP para realizar peticiones a APIs externas usando las funciones de WordPress.
 * 
 * @name HttpApiClient
 * @package WPDM\Core\Infrastructure\Api
 * @since 1.0.0
 */
class HttpApiClient
{
    /**
     * Envía una petición POST HTTP con el cuerpo y headers especificados.
     * 
     * @param string $url La URL del endpoint.
     * @param array<string, mixed> $body El cuerpo de la petición.
     * @param array<string, string> $headers Headers HTTP adicionales.
     * @param int $timeout Timeout en segundos.
     * @return array<string, mixed>|\WP_Error La respuesta HTTP o un objeto WP_Error en caso de fallo.
     */
    public function post(string $url, array $body, array $headers = [], int $timeout = 30): array|\WP_Error
    {
        $defaultHeaders = ['Content-Type' => 'application/json'];
        $mergedHeaders = array_merge($defaultHeaders, $headers);

        return wp_remote_post($url, [
            'headers' => $mergedHeaders,
            'body'    => wp_json_encode($body),
            'timeout' => $timeout,
        ]);
    }

    /**
     * Obtiene el código de respuesta HTTP de una respuesta.
     * 
     * @param array<string, mixed> $response
     * @return int
     */
    public function getResponseCode(array $response): int
    {
        return wp_remote_retrieve_response_code($response);
    }

    /**
     * Obtiene el cuerpo de la respuesta decodificado como array.
     * 
     * @param array<string, mixed> $response
     * @return array<string, mixed>|null
     */
    public function getResponseBody(array $response): ?array
    {
        $body = wp_remote_retrieve_body($response);
        $decoded = json_decode($body, true);
        
        return is_array($decoded) ? $decoded : null;
    }
}
