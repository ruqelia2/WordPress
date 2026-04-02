/**
 * WP DataCharts Pro — Frontend Chart Renderer
 *
 * Scans the page for .wpdcp-chart-container elements and initialises
 * each one by fetching chart configuration and rendering via the
 * appropriate chart library.
 */

( function () {
    'use strict';

    /**
     * Chart Renderer class.
     */
    class WPDCPChartRenderer {

        constructor() {
            /**
             * Map of active chart instances keyed by chart ID.
             * @type {Map<string, Object>}
             */
            this.instances = new Map();
        }

        /**
         * Scan for and initialise all chart containers in the document.
         */
        init() {
            const containers = document.querySelectorAll( '.wpdcp-chart-container' );

            if ( ! containers.length ) {
                return;
            }

            containers.forEach( ( container ) => {
                this.renderChart( container );
            } );
        }

        /**
         * Render a single chart container.
         *
         * Reads data attributes to determine chart identity and then fetches
         * configuration from the REST API placeholder.
         *
         * @param {HTMLElement} container The chart container element.
         */
        renderChart( container ) {
            const chartId   = container.dataset.chartId   || '';
            const chartType = container.dataset.chartType || 'line';
            const engine    = container.dataset.engine    || 'chartjs';

            if ( ! chartId ) {
                return;
            }

            // Mark as loading.
            container.classList.add( 'is-loading' );

            this.fetchChartConfig( chartId )
                .then( ( config ) => {
                    container.classList.remove( 'is-loading' );
                    this.mountChart( container, chartId, chartType, engine, config );
                } )
                .catch( ( error ) => {
                    container.classList.remove( 'is-loading' );
                    this.renderError( container, error.message || 'Unknown error' );
                } );
        }

        /**
         * Fetch chart configuration from the REST API.
         *
         * Phase 1 placeholder — returns a minimal config object.
         *
         * @param {string} chartId Chart ID.
         * @returns {Promise<Object>} Resolved chart config.
         */
        fetchChartConfig( chartId ) {
            // Phase 1: REST endpoint not yet registered; return placeholder config.
            return Promise.resolve( {
                id: chartId,
                data: {},
                options: {},
            } );
        }

        /**
         * Mount a chart into its canvas element.
         *
         * Phase 1 placeholder — outputs a data attribute summary to the canvas.
         *
         * @param {HTMLElement} container  Parent container element.
         * @param {string}      chartId    Chart ID.
         * @param {string}      chartType  Chart type slug.
         * @param {string}      engine     Rendering engine slug.
         * @param {Object}      config     Chart configuration object.
         */
        mountChart( container, chartId, chartType, engine, config ) {
            const canvas = container.querySelector( '.wpdcp-chart-canvas' );
            if ( ! canvas ) {
                return;
            }

            // Store instance reference.
            this.instances.set( chartId, { container, canvas, chartType, engine, config } );

            // Phase 1: annotate canvas with debug info until a real library is loaded.
            canvas.setAttribute( 'data-ready', 'true' );
            canvas.setAttribute( 'aria-label', 'Chart ' + chartId + ' (' + chartType + ')' );
        }

        /**
         * Destroy and clean up a chart instance.
         *
         * @param {string} chartId Chart ID to destroy.
         */
        destroy( chartId ) {
            const instance = this.instances.get( chartId );

            if ( ! instance ) {
                return;
            }

            const { canvas } = instance;
            if ( canvas ) {
                canvas.removeAttribute( 'data-ready' );
            }

            this.instances.delete( chartId );
        }

        /**
         * Render an inline error message inside a chart container.
         *
         * @param {HTMLElement} container Parent container element.
         * @param {string}      message   Error message text.
         */
        renderError( container, message ) {
            const div = document.createElement( 'div' );
            div.className = 'wpdcp-chart-error';
            div.textContent = message;
            container.appendChild( div );
        }
    }

    // Instantiate and auto-init on DOMContentLoaded.
    const renderer = new WPDCPChartRenderer();

    document.addEventListener( 'DOMContentLoaded', function () {
        renderer.init();
    } );

    // Expose globally for programmatic access.
    window.WPDCPChartRenderer = renderer;
} )();
