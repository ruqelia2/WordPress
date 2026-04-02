<?php
/**
 * Asset registration and enqueueing
 *
 * @package WPDCP\Core
 */

declare(strict_types=1);

namespace WPDCP\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Utils\Helpers;

/**
 * Class Assets
 *
 * Registers and enqueues CSS and JavaScript assets for both the admin
 * interface and the frontend.
 */
class Assets {

    /**
     * Hook into WordPress to register assets.
     */
    public function register(): void {
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAdminAssets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueueFrontendAssets' ] );
    }

    /**
     * Enqueue admin-specific CSS and JavaScript.
     *
     * Only loaded on plugin admin pages.
     *
     * @param string $hook Current admin page hook suffix.
     */
    public function enqueueAdminAssets( string $hook ): void {
        if ( ! Helpers::isPluginAdminPage() ) {
            return;
        }

        wp_enqueue_style(
            'wpdcp-admin',
            Helpers::pluginUrl( 'assets/css/admin.css' ),
            [],
            WPDCP_VERSION
        );

        wp_enqueue_script(
            'wpdcp-admin-app',
            Helpers::pluginUrl( 'assets/js/admin/app.js' ),
            [ 'jquery' ],
            WPDCP_VERSION,
            true
        );

        wp_localize_script(
            'wpdcp-admin-app',
            'wpdcpAdmin',
            [
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'restUrl' => esc_url_raw( rest_url( 'wpdcp/v1' ) ),
                'nonce'   => wp_create_nonce( 'wpdcp_admin_nonce' ),
                'version' => WPDCP_VERSION,
            ]
        );
    }

    /**
     * Enqueue frontend-specific CSS and JavaScript.
     */
    public function enqueueFrontendAssets(): void {
        wp_enqueue_style(
            'wpdcp-frontend',
            Helpers::pluginUrl( 'assets/css/frontend.css' ),
            [],
            WPDCP_VERSION
        );

        wp_enqueue_script(
            'wpdcp-chart-renderer',
            Helpers::pluginUrl( 'assets/js/frontend/chart-renderer.js' ),
            [],
            WPDCP_VERSION,
            true
        );
    }
}
