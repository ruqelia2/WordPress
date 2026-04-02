<?php
/**
 * Unit tests for the Sanitizer class
 *
 * @package WPDCP\Tests\Unit
 */

declare(strict_types=1);

namespace WPDCP\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WPDCP\Security\Sanitizer;

/**
 * Class SanitizerTest
 */
class SanitizerTest extends TestCase {

    // -------------------------------------------------------------------------
    // text()
    // -------------------------------------------------------------------------

    public function test_sanitize_text_returns_plain_string(): void {
        $this->assertSame( 'Hello World', Sanitizer::text( 'Hello World' ) );
    }

    public function test_sanitize_text_strips_html_tags(): void {
        $result = Sanitizer::text( '<script>alert("xss")</script>Hello' );
        $this->assertStringNotContainsString( '<script>', $result );
    }

    public function test_sanitize_text_empty_string(): void {
        $this->assertSame( '', Sanitizer::text( '' ) );
    }

    // -------------------------------------------------------------------------
    // int()
    // -------------------------------------------------------------------------

    public function test_sanitize_int_casts_string_to_int(): void {
        $this->assertSame( 42, Sanitizer::int( '42' ) );
    }

    public function test_sanitize_int_casts_float_to_int(): void {
        $this->assertSame( 3, Sanitizer::int( 3.9 ) );
    }

    public function test_sanitize_int_handles_negative(): void {
        $this->assertSame( -10, Sanitizer::int( '-10' ) );
    }

    public function test_sanitize_int_non_numeric_returns_zero(): void {
        $this->assertSame( 0, Sanitizer::int( 'not-a-number' ) );
    }

    // -------------------------------------------------------------------------
    // json()
    // -------------------------------------------------------------------------

    public function test_sanitize_json_valid_json_returns_encoded(): void {
        $input  = '{"key":"value"}';
        $result = Sanitizer::json( $input );
        $decoded = json_decode( $result, true );
        $this->assertIsArray( $decoded );
        $this->assertSame( 'value', $decoded['key'] );
    }

    public function test_sanitize_json_invalid_json_returns_empty_object(): void {
        $result = Sanitizer::json( 'not valid json {' );
        $this->assertSame( '{}', $result );
    }

    public function test_sanitize_json_empty_string_returns_empty_object(): void {
        $result = Sanitizer::json( '' );
        $this->assertSame( '{}', $result );
    }

    public function test_sanitize_json_array_input(): void {
        $input  = '[1,2,3]';
        $result = Sanitizer::json( $input );
        $decoded = json_decode( $result, true );
        $this->assertIsArray( $decoded );
        $this->assertCount( 3, $decoded );
    }

    // -------------------------------------------------------------------------
    // array()
    // -------------------------------------------------------------------------

    public function test_sanitize_array_applies_schema(): void {
        $input  = [ 'name' => '<b>John</b>', 'age' => '30', 'email' => 'john@example.com' ];
        $schema = [ 'name' => 'text', 'age' => 'int', 'email' => 'email' ];
        $result = Sanitizer::array( $input, $schema );

        $this->assertSame( 30, $result['age'] );
        $this->assertSame( 'john@example.com', $result['email'] );
        $this->assertStringNotContainsString( '<b>', $result['name'] );
    }

    public function test_sanitize_array_skips_undefined_schema_keys(): void {
        $input  = [ 'title' => 'Hello', 'extra' => 'data' ];
        $schema = [ 'title' => 'text' ];
        $result = Sanitizer::array( $input, $schema );

        $this->assertArrayHasKey( 'title', $result );
        $this->assertArrayNotHasKey( 'extra', $result );
    }

    // -------------------------------------------------------------------------
    // chartConfig()
    // -------------------------------------------------------------------------

    public function test_sanitize_chart_config_sanitizes_nested_array(): void {
        $config = [
            'type'    => 'line',
            'options' => [
                'title' => '<script>evil</script>',
                'value' => 42,
            ],
        ];

        $result = Sanitizer::chartConfig( $config );

        $this->assertSame( 'line', $result['type'] );
        $this->assertStringNotContainsString( '<script>', $result['options']['title'] );
        $this->assertSame( 42, $result['options']['value'] );
    }
}
