<?php
/**
 * Frontend Template: Chart Container
 *
 * Variables available in this template:
 *   $chart      - stdClass chart database row
 *   $chartId    - int Chart ID
 *   $style      - string Inline CSS for width/height
 *   $extra_class - string Additional CSS classes
 *
 * @package WP_DataCharts_Pro
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div
    class="wpdcp-chart-container<?php echo esc_attr( $extra_class ); ?>"
    style="<?php echo esc_attr( $style ); ?>"
    data-chart-id="<?php echo esc_attr( (string) $chartId ); ?>"
    data-chart-type="<?php echo esc_attr( $chart->chart_type ); ?>"
    data-engine="<?php echo esc_attr( $chart->engine ); ?>"
    role="img"
    aria-label="<?php echo esc_attr( $chart->title ); ?>"
>
    <div class="wpdcp-chart-loading" aria-hidden="true">
        <span class="wpdcp-spinner"></span>
    </div>
    <canvas class="wpdcp-chart-canvas" aria-hidden="true"></canvas>
</div>
