<?php
/**
 * Capability Manager
 *
 * @package WPDCP\Security
 */

declare(strict_types=1);

namespace WPDCP\Security;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class CapabilityManager
 *
 * Registers and removes custom WordPress capabilities for the plugin,
 * and provides convenience methods for current-user capability checks.
 */
class CapabilityManager {

    /** Capability: manage (create/edit/delete/settings) all charts. */
    public const MANAGE_CHARTS = 'manage_wpdcp_charts';

    /** Capability: create and edit charts. */
    public const EDIT_CHARTS = 'edit_wpdcp_charts';

    /** Capability: view chart admin screens. */
    public const VIEW_CHARTS = 'view_wpdcp_charts';

    /** Capability: manage data sources. */
    public const MANAGE_DATA_SOURCES = 'manage_wpdcp_data_sources';

    /** Capability: manage plugin settings. */
    public const MANAGE_SETTINGS = 'manage_wpdcp_settings';

    /**
     * Add all custom capabilities to the appropriate roles.
     *
     * Administrator — all capabilities.
     * Editor — edit and view capabilities.
     */
    public static function addCapabilities(): void {
        $admin_caps = [
            self::MANAGE_CHARTS,
            self::EDIT_CHARTS,
            self::VIEW_CHARTS,
            self::MANAGE_DATA_SOURCES,
            self::MANAGE_SETTINGS,
        ];

        $editor_caps = [
            self::EDIT_CHARTS,
            self::VIEW_CHARTS,
        ];

        $administrator = get_role( 'administrator' );
        if ( $administrator instanceof \WP_Role ) {
            foreach ( $admin_caps as $cap ) {
                $administrator->add_cap( $cap );
            }
        }

        $editor = get_role( 'editor' );
        if ( $editor instanceof \WP_Role ) {
            foreach ( $editor_caps as $cap ) {
                $editor->add_cap( $cap );
            }
        }
    }

    /**
     * Remove all custom plugin capabilities from every role.
     */
    public static function removeCapabilities(): void {
        global $wp_roles;

        if ( ! isset( $wp_roles ) ) {
            $wp_roles = new \WP_Roles();
        }

        $caps = [
            self::MANAGE_CHARTS,
            self::EDIT_CHARTS,
            self::VIEW_CHARTS,
            self::MANAGE_DATA_SOURCES,
            self::MANAGE_SETTINGS,
        ];

        foreach ( array_keys( $wp_roles->roles ) as $role_name ) {
            $role = get_role( $role_name );
            if ( $role instanceof \WP_Role ) {
                foreach ( $caps as $cap ) {
                    $role->remove_cap( $cap );
                }
            }
        }
    }

    /**
     * Check whether the current user can manage charts.
     *
     * @return bool
     */
    public static function canManageCharts(): bool {
        return current_user_can( self::MANAGE_CHARTS );
    }

    /**
     * Check whether the current user can edit charts.
     *
     * @return bool
     */
    public static function canEditCharts(): bool {
        return current_user_can( self::EDIT_CHARTS );
    }

    /**
     * Check whether the current user can view chart admin pages.
     *
     * @return bool
     */
    public static function canViewCharts(): bool {
        return current_user_can( self::VIEW_CHARTS );
    }
}
