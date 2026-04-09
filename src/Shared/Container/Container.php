<?php

declare(strict_types=1);

namespace WPDM\Shared\Container;

/**
 * Permite gestionar las dependencias del plugin WP Data Merge de manera centralizada, permitiendo registrar y resolver las instancias de las clases utilizadas en el plugin a través de un contenedor de dependencias simple.
 * 
 * @name Container
 * @package WPDM\Shared\Container
 * @since 1.0.0
 */
class Container
{

    private array $bindings = [];

    /**
     * Registra una nueva dependencia en el contenedor, asociando una clave con un resolver que devuelve la instancia de la clase correspondiente.
     * 
     * @param string $key La clave que identifica la dependencia a registrar en el contenedor.
     * @param callable $resolver Una función anónima que devuelve la instancia de la clase correspondiente a la clave proporcionada, permitiendo resolver las dependencias de manera flexible y centralizada.
     * @return void
     */
    public function bind(string $key, callable $resolver): void
    {
        $this->bindings[$key] = $resolver;
    }

    /**
     * Resuelve una dependencia registrada en el contenedor, obteniendo la instancia de la clase correspondiente a la clave proporcionada. Si la clave no está registrada en el contenedor, se lanza una excepción indicando que no existe un binding para esa clave.
     * 
     * @param string $key La clave que identifica la dependencia a resolver en el contenedor.
     * @return object La instancia de la clase correspondiente a la clave proporcionada, obtenida a través del resolver registrado en el contenedor.
     * @throws \Exception
     */
    public function get(string $key): object
    {
        if (!isset($this->bindings[$key])) {
            throw new \Exception("No binding for $key");
        }

        return $this->bindings[$key]($this);
    }
}
