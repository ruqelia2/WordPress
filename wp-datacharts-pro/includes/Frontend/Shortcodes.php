<?php
/**
 * Shortcode Registration
 *
 * @package WPDCP\Frontend
 */

declare(strict_types=1);

namespace WPDCP\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Core\Container;

/**
 * Class Shortcodes
 *
 * Registers the [wpdcp_chart] shortcode.
 */
class Shortcodes {

    /** @var Container DI container. */
    private Container $container;

    /**
     * @param Container $container DI container.
     */
    public function __construct( Container $container ) {
        $this->container = $container;
    }

    /**
     * Register all shortcodes.
     */
    public function register(): void {
        add_shortcode( 'wpdcp_chart', [ $this, 'renderChart' ] );
    }

    /**
     * Render the chart shortcode.
     *
     * Accepted attributes:
     * - id (required) : Chart ID.
     * - width         : CSS width (e.g. '100%', '600px').
     * - height        : CSS height.
     * - class         : Additional CSS classes.
     *
     * @param array<string, string>|string $atts Shortcode attributes.
     * @return string Rendered HTML.
     */
    public function renderChart( array|string $atts ): string {
        $atts = shortcode_atts(
            [
                'id'     => '0',
                'width'  => '100%',
                'height' => 'auto',
                'class'  => '',
            ],
            (array) $atts,
            'wpdcp_chart'
        );

        $chart_id = (int) $atts['id'];

        if ( $chart_id <= 0 ) {
            return '<!-- wpdcp_chart: missing or invalid id -->';
        }

        return ChartDisplay::render(
            $chart_id,
            [
                'width'  => sanitize_text_field( $atts['width'] ),
                'height' => sanitize_text_field( $atts['height'] ),
                'class'  => sanitize_html_class( $atts['class'] ),
            ]
        );
    }
}
