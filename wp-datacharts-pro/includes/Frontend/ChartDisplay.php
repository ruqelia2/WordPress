<?php
/**
 * Chart Display — renders chart HTML on the frontend
 *
 * @package WPDCP\Frontend
 */

declare(strict_types=1);

namespace WPDCP\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

use WPDCP\Database\ChartRepository;

/**
 * Class ChartDisplay
 *
 * Generates the HTML container element used by the JavaScript renderer.
 */
class ChartDisplay {

    /**
     * Render the HTML for a chart.
     *
     * @param int                  $chartId Chart ID.
     * @param array<string, mixed> $attrs   Optional display attributes (width, height, class).
     * @return string Rendered HTML string.
     */
    public static function render( int $chartId, array $attrs = [] ): string {
        $repository = new ChartRepository();
        $chart      = $repository->find( $chartId );

        if ( null === $chart ) {
            return sprintf( '<!-- wpdcp: chart %d not found -->', $chartId );
        }

        // Enqueue frontend assets.
        if ( ! is_admin() ) {
            wp_enqueue_style( 'wpdcp-frontend' );
            wp_enqueue_script( 'wpdcp-chart-renderer' );
        }

        $width  = ! empty( $attrs['width'] ) ? esc_attr( $attrs['width'] ) : '100%';
        $height = ! empty( $attrs['height'] ) && 'auto' !== $attrs['height']
            ? esc_attr( $attrs['height'] )
            : '';

        $extra_class = ! empty( $attrs['class'] ) ? ' ' . esc_attr( $attrs['class'] ) : '';

        $style = 'width:' . $width . ';';
        if ( $height ) {
            $style .= 'height:' . $height . ';';
        }

        ob_start();
        include WPDCP_PLUGIN_DIR . 'templates/frontend/chart-container.php';
        $html = ob_get_clean();

        /**
         * Filters the final chart HTML output.
         *
         * @param string $html    The rendered HTML.
         * @param int    $chartId The chart ID.
         * @param object $chart   The chart database row.
         */
        return (string) apply_filters( 'wpdcp_chart_html', $html, $chartId, $chart );
    }
}
