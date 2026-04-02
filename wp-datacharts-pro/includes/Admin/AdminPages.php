<?php
/**
 * Admin Page Render Callbacks
 *
 * @package WPDCP\Admin
 */

declare(strict_types=1);

namespace WPDCP\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Core\Container;

/**
 * Class AdminPages
 *
 * Loads template files for each admin sub-page.
 */
class AdminPages {

    /** @var Container DI container passed to templates. */
    private Container $container;

    /**
     * @param Container $container DI container.
     */
    public function __construct( Container $container ) {
        $this->container = $container;
    }

    /**
     * Render the Dashboard page.
     */
    public function renderDashboard(): void {
        $this->loadTemplate( 'dashboard' );
    }

    /**
     * Render the All Charts page.
     */
    public function renderAllCharts(): void {
        $this->loadTemplate( 'all-charts' );
    }

    /**
     * Render the Add New Chart page.
     */
    public function renderAddChart(): void {
        $this->loadTemplate( 'add-chart' );
    }

    /**
     * Render the Data Sources page.
     */
    public function renderDataSources(): void {
        $this->loadTemplate( 'data-sources' );
    }

    /**
     * Render the Templates page.
     */
    public function renderTemplates(): void {
        $this->loadTemplate( 'templates' );
    }

    /**
     * Render the Settings page.
     */
    public function renderSettings(): void {
        $this->loadTemplate( 'settings' );
    }

    /**
     * Load an admin template file.
     *
     * Provides the $container variable to the template.
     *
     * @param string $template Template slug (without .php extension).
     */
    private function loadTemplate( string $template ): void {
        $path = WPDCP_PLUGIN_DIR . 'templates/admin/' . $template . '.php';

        if ( file_exists( $path ) ) {
            $container = $this->container; // phpcs:ignore WordPress.NamingConventions.ValidVariableName
            include $path;
        } else {
            echo '<div class="wrap"><h1>' . esc_html__( 'Page not found.', 'wp-datacharts-pro' ) . '</h1></div>';
        }
    }
}
