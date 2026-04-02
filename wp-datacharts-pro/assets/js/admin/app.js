/**
 * WP DataCharts Pro — Admin Application
 *
 * Initialises admin UI behaviour.
 */

/* global wpdcpAdmin */

( function () {
    'use strict';

    /**
     * Admin application object.
     */
    const WPDCPAdmin = {

        /**
         * Localised data injected by wp_localize_script().
         * @type {{ ajaxUrl: string, restUrl: string, nonce: string, version: string }}
         */
        config: typeof wpdcpAdmin !== 'undefined' ? wpdcpAdmin : {},

        /**
         * Initialise the admin application.
         */
        init() {
            console.log( '[WP DataCharts Pro] Admin v' + ( this.config.version || 'unknown' ) + ' initialised.' );
            this.bindEvents();
        },

        /**
         * Bind DOM event listeners.
         */
        bindEvents() {
            document.addEventListener( 'click', function ( event ) {
                const target = /** @type {HTMLElement} */ ( event.target );

                if ( target.matches( '.wpdcp-action-btn' ) ) {
                    event.preventDefault();
                    WPDCPAdmin.handleActionButton( target );
                }
            } );
        },

        /**
         * Handle generic action button clicks.
         *
         * @param {HTMLElement} button The clicked button element.
         */
        handleActionButton( button ) {
            const action = button.dataset.action || '';
            if ( action ) {
                WPDCPAdmin.ajax( action, { id: button.dataset.id || 0 } )
                    .then( function ( response ) {
                        if ( response.success ) {
                            window.location.reload();
                        }
                    } )
                    .catch( function ( error ) {
                        console.error( '[WP DataCharts Pro] AJAX error:', error );
                    } );
            }
        },

        /**
         * Perform an AJAX request to WordPress admin-ajax.php.
         *
         * @param {string} action WordPress AJAX action name.
         * @param {Object} data   Additional POST data.
         * @returns {Promise<Object>} Parsed JSON response.
         */
        ajax( action, data = {} ) {
            const body = new URLSearchParams( {
                action: 'wpdcp_' + action,
                nonce: this.config.nonce || '',
                ...data,
            } );

            return fetch( this.config.ajaxUrl || '', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: body.toString(),
            } ).then( function ( res ) {
                return res.json();
            } );
        },

        /**
         * Perform a REST API request.
         *
         * @param {string} endpoint REST endpoint (relative to wpdcp/v1 base).
         * @param {RequestInit} options Fetch options.
         * @returns {Promise<Object>} Parsed JSON response.
         */
        rest( endpoint, options = {} ) {
            const url = ( this.config.restUrl || '' ).replace( /\/$/, '' ) + '/' + endpoint.replace( /^\//, '' );

            const defaults = {
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': this.config.nonce || '',
                },
            };

            return fetch( url, { ...defaults, ...options } ).then( function ( res ) {
                return res.json();
            } );
        },
    };

    document.addEventListener( 'DOMContentLoaded', function () {
        WPDCPAdmin.init();
    } );

    // Expose globally for extensibility.
    window.WPDCPAdmin = WPDCPAdmin;
} )();
