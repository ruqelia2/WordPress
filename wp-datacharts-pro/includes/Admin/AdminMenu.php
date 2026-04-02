<?php
/**
 * Admin Menu Registration
 *
 * @package WPDCP\Admin
 */

declare(strict_types=1);

namespace WPDCP\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Core\Container;
use WPDCP\Security\CapabilityManager;

/**
 * Class AdminMenu
 *
 * Registers the plugin's main menu item and all sub-menu pages.
 */
class AdminMenu {

    /** @var Container DI container. */
    private Container $container;

    /**
     * @param Container $container DI container.
     */
    public function __construct( Container $container ) {
        $this->container = $container;
    }

    /**
     * Hook into admin_menu to register all menu items.
     */
    public function register(): void {
        add_action( 'admin_menu', [ $this, 'addMenuPages' ] );
    }

    /**
     * Add the top-level menu and all sub-menus.
     */
    public function addMenuPages(): void {
        $pages = new AdminPages( $this->container );

        // Top-level menu.
        add_menu_page(
            __( 'DataCharts Pro', 'wp-datacharts-pro' ),
            __( 'DataCharts Pro', 'wp-datacharts-pro' ),
            CapabilityManager::MANAGE_CHARTS,
            'wpdcp-dashboard',
            [ $pages, 'renderDashboard' ],
            'dashicons-chart-area',
            58
        );

        // Dashboard (mirrors the top-level menu entry).
        add_submenu_page(
            'wpdcp-dashboard',
            __( 'Dashboard', 'wp-datacharts-pro' ),
            __( 'Dashboard', 'wp-datacharts-pro' ),
            CapabilityManager::MANAGE_CHARTS,
            'wpdcp-dashboard',
            [ $pages, 'renderDashboard' ]
        );

        // All Charts.
        add_submenu_page(
            'wpdcp-dashboard',
            __( 'All Charts', 'wp-datacharts-pro' ),
            __( 'All Charts', 'wp-datacharts-pro' ),
            CapabilityManager::VIEW_CHARTS,
            'wpdcp-charts',
            [ $pages, 'renderAllCharts' ]
        );

        // Add New Chart.
        add_submenu_page(
            'wpdcp-dashboard',
            __( 'Add New Chart', 'wp-datacharts-pro' ),
            __( 'Add New Chart', 'wp-datacharts-pro' ),
            CapabilityManager::EDIT_CHARTS,
            'wpdcp-add-chart',
            [ $pages, 'renderAddChart' ]
        );

        // Data Sources.
        add_submenu_page(
            'wpdcp-dashboard',
            __( 'Data Sources', 'wp-datacharts-pro' ),
            __( 'Data Sources', 'wp-datacharts-pro' ),
            CapabilityManager::MANAGE_DATA_SOURCES,
            'wpdcp-data-sources',
            [ $pages, 'renderDataSources' ]
        );

        // Templates.
        add_submenu_page(
            'wpdcp-dashboard',
            __( 'Templates', 'wp-datacharts-pro' ),
            __( 'Templates', 'wp-datacharts-pro' ),
            CapabilityManager::MANAGE_CHARTS,
            'wpdcp-templates',
            [ $pages, 'renderTemplates' ]
        );

        // Settings.
        add_submenu_page(
            'wpdcp-dashboard',
            __( 'Settings', 'wp-datacharts-pro' ),
            __( 'Settings', 'wp-datacharts-pro' ),
            CapabilityManager::MANAGE_SETTINGS,
            'wpdcp-settings',
            [ $pages, 'renderSettings' ]
        );
    }
}
