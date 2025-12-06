<?php
namespace App\Core;

/**
 * Simple Dependency Injection Container
 * Manages application dependencies and their lifecycle
 */
class Container
{
    private static $instance = null;
    private $bindings = [];
    private $instances = [];

    /**
     * Singleton instance
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Bind a concrete implementation to an abstract type
     * @param string $abstract The interface or class name
     * @param callable|string|null $concrete The implementation (closure, class name, or null for auto-resolve)
     * @param bool $singleton Whether to create a singleton instance
     */
    public function bind($abstract, $concrete = null, $singleton = false)
    {
        if ($concrete === null) {
            $concrete = $abstract;
        }

        $this->bindings[$abstract] = [
            'concrete' => $concrete,
            'singleton' => $singleton
        ];
    }

    /**
     * Bind a singleton instance
     */
    public function singleton($abstract, $concrete = null)
    {
        $this->bind($abstract, $concrete, true);
    }

    /**
     * Register an existing instance as a singleton
     */
    public function instance($abstract, $instance)
    {
        $this->instances[$abstract] = $instance;
    }

    /**
     * Resolve a dependency from the container
     */
    public function make($abstract, array $parameters = [])
    {
        // Return existing singleton instance
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        // Get binding or use abstract as concrete
        $concrete = $this->bindings[$abstract]['concrete'] ?? $abstract;
        $isSingleton = $this->bindings[$abstract]['singleton'] ?? false;

        // Resolve the concrete implementation
        if ($concrete instanceof \Closure) {
            $object = $concrete($this, $parameters);
        } else {
            $object = $this->build($concrete, $parameters);
        }

        // Store singleton instance
        if ($isSingleton) {
            $this->instances[$abstract] = $object;
        }

        return $object;
    }

    /**
     * Build a concrete class instance with dependency injection
     */
    private function build($concrete, array $parameters = [])
    {
        try {
            $reflection = new \ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new \Exception("Target class [{$concrete}] does not exist.");
        }

        // Check if class is instantiable
        if (!$reflection->isInstantiable()) {
            throw new \Exception("Target [{$concrete}] is not instantiable.");
        }

        $constructor = $reflection->getConstructor();

        // If no constructor, just instantiate
        if ($constructor === null) {
            return new $concrete;
        }

        // Resolve constructor dependencies
        $dependencies = $constructor->getParameters();
        $instances = $this->resolveDependencies($dependencies, $parameters);

        return $reflection->newInstanceArgs($instances);
    }

    /**
     * Resolve constructor dependencies
     */
    private function resolveDependencies(array $dependencies, array $parameters = [])
    {
        $results = [];

        foreach ($dependencies as $dependency) {
            // If parameter is provided explicitly, use it
            if (isset($parameters[$dependency->getName()])) {
                $results[] = $parameters[$dependency->getName()];
                continue;
            }

            // Get dependency type
            $type = $dependency->getType();
            
            if ($type === null) {
                // No type hint - check for default value
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve dependency [{$dependency->getName()}] without type hint or default value.");
                }
            } elseif ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                // Type hint exists and is not a built-in type - resolve from container
                $results[] = $this->make($type->getName());
            } else {
                // Built-in type without default value
                if ($dependency->isDefaultValueAvailable()) {
                    $results[] = $dependency->getDefaultValue();
                } else {
                    throw new \Exception("Cannot resolve built-in type dependency [{$dependency->getName()}].");
                }
            }
        }

        return $results;
    }

    /**
     * Check if binding exists
     */
    public function has($abstract)
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /**
     * Remove a binding
     */
    public function forget($abstract)
    {
        unset($this->bindings[$abstract], $this->instances[$abstract]);
    }

    /**
     * Flush all bindings and instances
     */
    public function flush()
    {
        $this->bindings = [];
        $this->instances = [];
    }
}
