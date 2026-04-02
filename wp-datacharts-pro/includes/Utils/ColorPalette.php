<?php
/**
 * Color Palette Utility
 *
 * @package WPDCP\Utils
 */

declare(strict_types=1);

namespace WPDCP\Utils;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class ColorPalette
 *
 * Provides built-in color palettes for chart rendering.
 */
class ColorPalette {

    /**
     * Built-in color palettes.
     * Each palette contains exactly 10 hex color strings.
     *
     * @var array<string, string[]>
     */
    private static array $palettes = [
        'default' => [
            '#4e79a7', '#f28e2b', '#e15759', '#76b7b2',
            '#59a14f', '#edc948', '#b07aa1', '#ff9da7',
            '#9c755f', '#bab0ac',
        ],
        'pastel' => [
            '#aec6cf', '#ffb347', '#b39eb5', '#ff6961',
            '#77dd77', '#fdfd96', '#84b6f4', '#fdcae1',
            '#cfcfc4', '#b5ead7',
        ],
        'vibrant' => [
            '#e63946', '#457b9d', '#2a9d8f', '#e9c46a',
            '#f4a261', '#264653', '#e76f51', '#06d6a0',
            '#118ab2', '#073b4c',
        ],
        'monochrome' => [
            '#f7f7f7', '#d9d9d9', '#bdbdbd', '#969696',
            '#737373', '#525252', '#252525', '#000000',
            '#cccccc', '#888888',
        ],
        'business' => [
            '#003f5c', '#2f4b7c', '#665191', '#a05195',
            '#d45087', '#f95d6a', '#ff7c43', '#ffa600',
            '#58508d', '#bc5090',
        ],
    ];

    /**
     * Return all colors in a named palette.
     *
     * Applies the 'wpdcp_color_palettes' filter so third parties can add palettes.
     *
     * @param string $name Palette name (default: 'default').
     * @return string[] Array of hex color strings.
     */
    public static function getPalette( string $name = 'default' ): array {
        $palettes = apply_filters( 'wpdcp_color_palettes', self::$palettes );

        return $palettes[ $name ] ?? $palettes['default'] ?? [];
    }

    /**
     * Return all available palette names.
     *
     * @return string[] Array of palette name strings.
     */
    public static function getAvailablePalettes(): array {
        $palettes = apply_filters( 'wpdcp_color_palettes', self::$palettes );

        return array_keys( (array) $palettes );
    }

    /**
     * Return a single color from a palette, cycling if the index exceeds the palette size.
     *
     * @param string $palette Palette name.
     * @param int    $index   Zero-based color index (wraps cyclically).
     * @return string Hex color string.
     */
    public static function getColor( string $palette, int $index ): string {
        $colors = self::getPalette( $palette );

        if ( empty( $colors ) ) {
            return '#000000';
        }

        return $colors[ $index % count( $colors ) ];
    }
}
