<?php
/**
 * Simple Dependency Injection Container
 *
 * @package WPDCP\Core
 */

declare(strict_types=1);

namespace WPDCP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use Closure;
use ReflectionClass;
use ReflectionNamedType;
use RuntimeException;

/**
 * Class Container
 *
 * Lightweight DI container supporting singletons, bindings, and
 * automatic resolution via reflection.
 */
class Container {

    /** @var array<string, Closure> Registered factories. */
    private array $bindings = [];

    /** @var array<string, mixed> Resolved singletons. */
    private array $singletons = [];

    /** @var array<string, bool> Marks which abstracts are singletons. */
    private array $singletonMarks = [];

    /**
     * Register an abstract as a singleton.
     *
     * @param string  $abstract Abstract class/interface identifier.
     * @param Closure $factory  Factory closure that creates the instance.
     */
    public function singleton( string $abstract, Closure $factory ): void {
        $this->bindings[ $abstract ]      = $factory;
        $this->singletonMarks[ $abstract ] = true;
    }

    /**
     * Register an abstract with a factory (new instance on every get()).
     *
     * @param string  $abstract Abstract class/interface identifier.
     * @param Closure $factory  Factory closure that creates the instance.
     */
    public function bind( string $abstract, Closure $factory ): void {
        $this->bindings[ $abstract ]       = $factory;
        $this->singletonMarks[ $abstract ] = false;
    }

    /**
     * Resolve an abstract from the container.
     *
     * Falls back to reflection-based auto-resolution when no binding exists.
     *
     * @param string $abstract Abstract class/interface identifier.
     * @return mixed Resolved instance.
     * @throws RuntimeException When the abstract cannot be resolved.
     */
    public function get( string $abstract ): mixed {
        // Return already-resolved singleton.
        if ( isset( $this->singletons[ $abstract ] ) ) {
            return $this->singletons[ $abstract ];
        }

        // Use registered factory.
        if ( isset( $this->bindings[ $abstract ] ) ) {
            $instance = ( $this->bindings[ $abstract ] )( $this );

            if ( $this->singletonMarks[ $abstract ] ?? false ) {
                $this->singletons[ $abstract ] = $instance;
            }

            return $instance;
        }

        // Auto-resolve via reflection.
        return $this->autoResolve( $abstract );
    }

    /**
     * Check whether an abstract is registered or can be auto-resolved.
     *
     * @param string $abstract Abstract class/interface identifier.
     * @return bool
     */
    public function has( string $abstract ): bool {
        return isset( $this->bindings[ $abstract ] ) || class_exists( $abstract );
    }

    /**
     * Auto-resolve a class using PHP reflection.
     *
     * Recursively resolves constructor parameters.
     *
     * @param string $abstract FQCN of the class to instantiate.
     * @return mixed
     * @throws RuntimeException When instantiation is not possible.
     */
    private function autoResolve( string $abstract ): mixed {
        if ( ! class_exists( $abstract ) ) {
            throw new RuntimeException(
                sprintf( 'Cannot resolve abstract "%s": class does not exist.', $abstract )
            );
        }

        $reflection = new ReflectionClass( $abstract );

        if ( ! $reflection->isInstantiable() ) {
            throw new RuntimeException(
                sprintf( 'Cannot instantiate "%s".', $abstract )
            );
        }

        $constructor = $reflection->getConstructor();

        if ( null === $constructor ) {
            return $reflection->newInstance();
        }

        $dependencies = [];

        foreach ( $constructor->getParameters() as $param ) {
            $type = $param->getType();

            if ( $type instanceof ReflectionNamedType && ! $type->isBuiltin() ) {
                $dependencies[] = $this->get( $type->getName() );
            } elseif ( $param->isDefaultValueAvailable() ) {
                $dependencies[] = $param->getDefaultValue();
            } else {
                throw new RuntimeException(
                    sprintf(
                        'Cannot resolve parameter "%s" of "%s".',
                        $param->getName(),
                        $abstract
                    )
                );
            }
        }

        return $reflection->newInstanceArgs( $dependencies );
    }
}
