<?php
/**
 * Plugin Settings
 *
 * @package WPDCP\Admin
 */

declare(strict_types=1);

namespace WPDCP\Admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Security\CapabilityManager;

/**
 * Class Settings
 *
 * Registers plugin settings sections, fields, and retrieval helpers.
 */
class Settings {

    /** Settings group identifier. */
    public const SETTINGS_GROUP = 'wpdcp_settings_group';

    /** Settings section identifier. */
    public const SECTION_GENERAL = 'wpdcp_section_general';

    /**
     * Hook into admin_init to register settings.
     */
    public function register(): void {
        add_action( 'admin_init', [ $this, 'registerSettings' ] );
    }

    /**
     * Register settings, sections, and fields.
     */
    public function registerSettings(): void {
        // Register settings.
        register_setting(
            self::SETTINGS_GROUP,
            'wpdcp_default_engine',
            [
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => 'chartjs',
            ]
        );

        register_setting(
            self::SETTINGS_GROUP,
            'wpdcp_default_cache_ttl',
            [
                'type'              => 'integer',
                'sanitize_callback' => 'absint',
                'default'           => 3600,
            ]
        );

        register_setting(
            self::SETTINGS_GROUP,
            'wpdcp_enable_analytics',
            [
                'type'              => 'boolean',
                'sanitize_callback' => 'rest_sanitize_boolean',
                'default'           => true,
            ]
        );

        register_setting(
            self::SETTINGS_GROUP,
            'wpdcp_custom_css',
            [
                'type'              => 'string',
                'sanitize_callback' => 'wp_strip_all_tags',
                'default'           => '',
            ]
        );

        // General settings section.
        add_settings_section(
            self::SECTION_GENERAL,
            __( 'General Settings', 'wp-datacharts-pro' ),
            [ $this, 'renderSectionDescription' ],
            'wpdcp-settings'
        );

        // Default rendering engine field.
        add_settings_field(
            'wpdcp_default_engine',
            __( 'Default Rendering Engine', 'wp-datacharts-pro' ),
            [ $this, 'renderEngineField' ],
            'wpdcp-settings',
            self::SECTION_GENERAL
        );

        // Default cache TTL field.
        add_settings_field(
            'wpdcp_default_cache_ttl',
            __( 'Default Cache TTL (seconds)', 'wp-datacharts-pro' ),
            [ $this, 'renderCacheTtlField' ],
            'wpdcp-settings',
            self::SECTION_GENERAL
        );

        // Enable analytics field.
        add_settings_field(
            'wpdcp_enable_analytics',
            __( 'Enable Analytics', 'wp-datacharts-pro' ),
            [ $this, 'renderAnalyticsField' ],
            'wpdcp-settings',
            self::SECTION_GENERAL
        );

        // Custom CSS field.
        add_settings_field(
            'wpdcp_custom_css',
            __( 'Custom CSS', 'wp-datacharts-pro' ),
            [ $this, 'renderCustomCssField' ],
            'wpdcp-settings',
            self::SECTION_GENERAL
        );
    }

    /**
     * Render the section description.
     */
    public function renderSectionDescription(): void {
        echo '<p>' . esc_html__( 'Configure the global defaults for WP DataCharts Pro.', 'wp-datacharts-pro' ) . '</p>';
    }

    /**
     * Render the default engine select field.
     */
    public function renderEngineField(): void {
        $value   = self::getOption( 'wpdcp_default_engine', 'chartjs' );
        $engines = [ 'chartjs', 'highcharts', 'apexcharts', 'echarts' ];
        echo '<select name="wpdcp_default_engine" id="wpdcp_default_engine">';
        foreach ( $engines as $engine ) {
            printf(
                '<option value="%s"%s>%s</option>',
                esc_attr( $engine ),
                selected( $value, $engine, false ),
                esc_html( ucfirst( $engine ) )
            );
        }
        echo '</select>';
    }

    /**
     * Render the cache TTL number input.
     */
    public function renderCacheTtlField(): void {
        $value = (int) self::getOption( 'wpdcp_default_cache_ttl', 3600 );
        printf(
            '<input type="number" name="wpdcp_default_cache_ttl" id="wpdcp_default_cache_ttl" value="%d" min="0" step="1" class="small-text" />',
            $value
        );
        echo '<p class="description">' . esc_html__( 'Set to 0 to disable caching.', 'wp-datacharts-pro' ) . '</p>';
    }

    /**
     * Render the analytics enable checkbox.
     */
    public function renderAnalyticsField(): void {
        $checked = (bool) self::getOption( 'wpdcp_enable_analytics', true );
        printf(
            '<input type="checkbox" name="wpdcp_enable_analytics" id="wpdcp_enable_analytics" value="1"%s />',
            checked( $checked, true, false )
        );
        echo '<label for="wpdcp_enable_analytics"> ' . esc_html__( 'Track chart views and interactions.', 'wp-datacharts-pro' ) . '</label>';
    }

    /**
     * Render the custom CSS textarea.
     */
    public function renderCustomCssField(): void {
        $value = (string) self::getOption( 'wpdcp_custom_css', '' );
        printf(
            '<textarea name="wpdcp_custom_css" id="wpdcp_custom_css" rows="10" cols="50" class="large-text code">%s</textarea>',
            esc_textarea( $value )
        );
        echo '<p class="description">' . esc_html__( 'Custom CSS applied to all chart containers.', 'wp-datacharts-pro' ) . '</p>';
    }

    /**
     * Retrieve a plugin option with an optional default.
     *
     * @param string $key     Option name.
     * @param mixed  $default Default value when option is not set.
     * @return mixed Option value or default.
     */
    public static function getOption( string $key, mixed $default = null ): mixed {
        return get_option( $key, $default );
    }
}
