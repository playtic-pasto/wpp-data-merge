<?php

namespace WPDM\Shared\Container;

class Container
{

    private array $bindings = [];

    public function bind(string $key, callable $resolver): void
    {
        $this->bindings[$key] = $resolver;
    }

    public function get(string $key)
    {
        if (!isset($this->bindings[$key])) {
            throw new \Exception("No binding for $key");
        }

        return $this->bindings[$key]($this);
    }
}
