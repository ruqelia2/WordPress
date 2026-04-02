<?php
/**
 * Main Plugin Class (Singleton Bootstrap)
 *
 * @package WPDCP\Core
 */

declare(strict_types=1);

namespace WPDCP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Admin\AdminMenu;
use WPDCP\Admin\Settings;
use WPDCP\Cache\CacheManager;
use WPDCP\Database\ChartRepository;
use WPDCP\Database\DataSourceRepository;
use WPDCP\Frontend\GutenbergBlock;
use WPDCP\Frontend\Shortcodes;
use WPDCP\Security\Sanitizer;
use WPDCP\Security\Validator;

/**
 * Class Plugin
 *
 * Singleton bootstrap class responsible for initialising all plugin components.
 */
final class Plugin {

    /** @var Plugin|null Singleton instance. */
    private static ?Plugin $instance = null;

    /** @var Container Dependency injection container. */
    private Container $container;

    /**
     * Private constructor — use getInstance().
     */
    private function __construct() {
        $this->container = new Container();
    }

    /** Prevent cloning. */
    private function __clone(): void {}

    /** Prevent unserialization. */
    public function __wakeup(): void {
        throw new \RuntimeException( 'Cannot unserialize singleton.' );
    }

    /**
     * Return or create the singleton instance.
     */
    public static function getInstance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Initialise the plugin.
     */
    public function init(): void {
        $this->registerServices();

        ( new I18n() )->load();

        if ( is_admin() ) {
            ( new AdminMenu( $this->container ) )->register();
            ( new Settings() )->register();
        }

        ( new Shortcodes( $this->container ) )->register();
        ( new GutenbergBlock( $this->container ) )->register();

        add_action( 'rest_api_init', [ $this, 'registerRestRoutes' ] );

        ( new Assets() )->register();

        do_action( 'wpdcp_loaded', $this );
    }

    /**
     * Register REST API routes.
     *
     * Placeholder — no REST endpoints in Phase 1, but the method exists
     * so that the rest_api_init hook can be properly hooked later.
     */
    public function registerRestRoutes(): void {
        // Phase 2 will register REST routes here.
    }

    /**
     * Register core services into the DI container.
     */
    private function registerServices(): void {
        $this->container->singleton(
            CacheManager::class,
            static fn() => new CacheManager()
        );

        $this->container->singleton(
            ChartRepository::class,
            static fn() => new ChartRepository()
        );

        $this->container->singleton(
            DataSourceRepository::class,
            static fn() => new DataSourceRepository()
        );

        $this->container->singleton(
            Sanitizer::class,
            static fn() => new Sanitizer()
        );

        $this->container->singleton(
            Validator::class,
            static fn() => new Validator()
        );
    }

    /**
     * Return the DI container.
     */
    public function getContainer(): Container {
        return $this->container;
    }
}
