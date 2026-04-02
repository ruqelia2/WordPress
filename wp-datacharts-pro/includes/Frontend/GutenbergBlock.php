<?php
/**
 * Gutenberg Block Registration
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
 * Class GutenbergBlock
 *
 * Registers the server-side rendered wpdcp/chart Gutenberg block.
 */
class GutenbergBlock {

    /** @var Container DI container. */
    private Container $container;

    /**
     * @param Container $container DI container.
     */
    public function __construct( Container $container ) {
        $this->container = $container;
    }

    /**
     * Hook into init to register the block type.
     */
    public function register(): void {
        add_action( 'init', [ $this, 'registerBlock' ] );
    }

    /**
     * Register the wpdcp/chart block type with a server-side render callback.
     */
    public function registerBlock(): void {
        register_block_type(
            'wpdcp/chart',
            [
                'render_callback' => [ $this, 'renderBlock' ],
                'attributes'      => [
                    'chartId' => [
                        'type'    => 'number',
                        'default' => 0,
                    ],
                    'width'   => [
                        'type'    => 'string',
                        'default' => '100%',
                    ],
                    'height'  => [
                        'type'    => 'string',
                        'default' => 'auto',
                    ],
                    'className' => [
                        'type'    => 'string',
                        'default' => '',
                    ],
                ],
            ]
        );
    }

    /**
     * Server-side render callback for the block.
     *
     * @param array<string, mixed> $attributes Block attributes.
     * @return string Rendered HTML.
     */
    public function renderBlock( array $attributes ): string {
        $chart_id = (int) ( $attributes['chartId'] ?? 0 );

        if ( $chart_id <= 0 ) {
            return '<!-- wpdcp/chart: missing chart ID -->';
        }

        return ChartDisplay::render(
            $chart_id,
            [
                'width'  => sanitize_text_field( (string) ( $attributes['width'] ?? '100%' ) ),
                'height' => sanitize_text_field( (string) ( $attributes['height'] ?? 'auto' ) ),
                'class'  => sanitize_html_class( (string) ( $attributes['className'] ?? '' ) ),
            ]
        );
    }
}
