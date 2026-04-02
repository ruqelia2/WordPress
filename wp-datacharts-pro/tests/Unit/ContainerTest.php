<?php
/**
 * Unit tests for the Container class
 *
 * @package WPDCP\Tests\Unit
 */

declare(strict_types=1);

namespace WPDCP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use WPDCP\Core\Container;

/**
 * Class ContainerTest
 */
class ContainerTest extends TestCase {

    private Container $container;

    protected function setUp(): void {
        parent::setUp();
        $this->container = new Container();
    }

    // -------------------------------------------------------------------------
    // singleton()
    // -------------------------------------------------------------------------

    public function test_singleton_returns_same_instance(): void {
        $this->container->singleton( 'my_service', fn() => new \stdClass() );

        $a = $this->container->get( 'my_service' );
        $b = $this->container->get( 'my_service' );

        $this->assertSame( $a, $b );
    }

    public function test_singleton_stores_resolved_value(): void {
        $obj = new \stdClass();
        $obj->value = 'test';

        $this->container->singleton( 'obj', fn() => $obj );

        $resolved = $this->container->get( 'obj' );
        $this->assertSame( 'test', $resolved->value );
    }

    // -------------------------------------------------------------------------
    // bind()
    // -------------------------------------------------------------------------

    public function test_bind_returns_new_instance_each_time(): void {
        $this->container->bind( 'transient', fn() => new \stdClass() );

        $a = $this->container->get( 'transient' );
        $b = $this->container->get( 'transient' );

        $this->assertNotSame( $a, $b );
    }

    // -------------------------------------------------------------------------
    // has()
    // -------------------------------------------------------------------------

    public function test_has_returns_true_for_registered_binding(): void {
        $this->container->singleton( 'registered', fn() => new \stdClass() );

        $this->assertTrue( $this->container->has( 'registered' ) );
    }

    public function test_has_returns_true_for_existing_class(): void {
        $this->assertTrue( $this->container->has( \stdClass::class ) );
    }

    public function test_has_returns_false_for_unknown_abstract(): void {
        $this->assertFalse( $this->container->has( 'NonExistentClass_XYZ_12345' ) );
    }

    // -------------------------------------------------------------------------
    // Auto-resolution
    // -------------------------------------------------------------------------

    public function test_auto_resolves_class_without_constructor(): void {
        $instance = $this->container->get( \stdClass::class );
        $this->assertInstanceOf( \stdClass::class, $instance );
    }

    public function test_auto_resolve_throws_for_unknown_class(): void {
        $this->expectException( RuntimeException::class );
        $this->container->get( 'NonExistentClass_XYZ_12345' );
    }

    public function test_auto_resolve_creates_new_instance_each_time(): void {
        $a = $this->container->get( \stdClass::class );
        $b = $this->container->get( \stdClass::class );

        $this->assertNotSame( $a, $b );
    }

    // -------------------------------------------------------------------------
    // get() — registered singleton already resolved
    // -------------------------------------------------------------------------

    public function test_get_returns_cached_singleton_on_second_call(): void {
        $calls = 0;

        $this->container->singleton(
            'counter_service',
            function () use ( &$calls ) {
                $calls++;
                return new \stdClass();
            }
        );

        $this->container->get( 'counter_service' );
        $this->container->get( 'counter_service' );

        $this->assertSame( 1, $calls, 'Factory should be called only once for a singleton.' );
    }
}
