<?php
/**
 * Minimal WordPress stubs for plugin unit tests (no bootstrap).
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', '/tmp/wordpress/' );
}

/** @var array<string, mixed> */
$GLOBALS['rs_test_options'] = array();

/** @var array<string, mixed> */
$GLOBALS['rs_test_transients'] = array();

if ( ! function_exists( '__' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function __( string $text ): string {
		return $text;
	}
}

if ( ! function_exists( 'get_option' ) ) {
	/**
	 * @param string $key Option key.
	 * @param mixed  $default Default.
	 * @return mixed
	 */
	function get_option( string $key, $default = false ) {
		return $GLOBALS['rs_test_options'][ $key ] ?? $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	/**
	 * @param string $key Option key.
	 * @param mixed  $value Value.
	 * @return bool
	 */
	function update_option( string $key, $value, bool $autoload = true ): bool {
		$GLOBALS['rs_test_options'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_option' ) ) {
	/**
	 * @param string $key Option key.
	 * @return bool
	 */
	function delete_option( string $key ): bool {
		unset( $GLOBALS['rs_test_options'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_transient' ) ) {
	/**
	 * @param string $key Transient key.
	 * @return mixed
	 */
	function get_transient( string $key ) {
		return $GLOBALS['rs_test_transients'][ $key ] ?? false;
	}
}

if ( ! function_exists( 'set_transient' ) ) {
	/**
	 * @param string $key Transient key.
	 * @param mixed  $value Value.
	 * @param int    $expiration Expiration.
	 * @return bool
	 */
	function set_transient( string $key, $value, int $expiration ): bool {
		$GLOBALS['rs_test_transients'][ $key ] = $value;
		return true;
	}
}

if ( ! function_exists( 'delete_transient' ) ) {
	/**
	 * @param string $key Transient key.
	 * @return bool
	 */
	function delete_transient( string $key ): bool {
		unset( $GLOBALS['rs_test_transients'][ $key ] );
		return true;
	}
}

if ( ! function_exists( 'get_block_wrapper_attributes' ) ) {
	/**
	 * @param array<string, string> $extra Extra attributes.
	 * @return string
	 */
	function get_block_wrapper_attributes( array $extra = array() ): string {
		$parts = array();
		foreach ( $extra as $name => $value ) {
			$parts[] = $name . '="' . esc_attr( (string) $value ) . '"';
		}
		return implode( ' ', $parts );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function esc_attr( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

/**
 * @return array<string, mixed>
 */
function rs_sample_browser_payload(): array {
	return array(
		'schema_version' => 1,
		'html'           => '<div class="rs-embed">Hello</div>',
		'configuration'  => array(
			'theme'  => array( 'name' => 'rounded' ),
			'sizing' => array( 'width' => '100%' ),
		),
		'sdk'            => array( 'minimum_version' => '0.3.0' ),
	);
}

if ( ! function_exists( 'wp_unslash' ) ) {
	/**
	 * @param string|array $value Value.
	 * @return string|array
	 */
	function wp_unslash( $value ) {
		return $value;
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * @param string $value Value.
	 * @return string
	 */
	function sanitize_text_field( $value ): string {
		return trim( (string) $value );
	}
}

if ( ! function_exists( 'esc_url_raw' ) ) {
	/**
	 * @param string $url URL.
	 * @return string
	 */
	function esc_url_raw( $url ): string {
		return trim( (string) $url );
	}
}

if ( ! function_exists( 'esc_html__' ) ) {
	/**
	 * @param string $text Text.
	 * @return string
	 */
	function esc_html__( string $text ): string {
		return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'admin_url' ) ) {
	/**
	 * @param string $path Path.
	 * @return string
	 */
	function admin_url( string $path = '' ): string {
		return 'http://localhost:8888/wp-admin/' . ltrim( $path, '/' );
	}
}

if ( ! function_exists( 'add_query_arg' ) ) {
	/**
	 * @param mixed ...$args Query args and URL.
	 * @return string
	 */
	function add_query_arg( ...$args ): string {
		if ( is_array( $args[0] ) ) {
			$params = $args[0];
			$url    = isset( $args[1] ) ? (string) $args[1] : '';
		} else {
			$params = array( $args[0] => $args[1] );
			$url    = isset( $args[2] ) ? (string) $args[2] : '';
		}

		$parts = wp_parse_url( $url );
		$query = array();
		if ( ! empty( $parts['query'] ) ) {
			parse_str( (string) $parts['query'], $query );
		}
		foreach ( $params as $key => $value ) {
			$query[ $key ] = $value;
		}

		$scheme = isset( $parts['scheme'] ) ? $parts['scheme'] . '://' : '';
		$host   = $parts['host'] ?? '';
		$path   = $parts['path'] ?? '';
		return $scheme . $host . $path . '?' . http_build_query( $query );
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	/**
	 * @param string $url URL.
	 * @return array<string, mixed>|false
	 */
	function wp_parse_url( string $url ) {
		return parse_url( $url );
	}
}

if ( ! function_exists( 'wp_generate_password' ) ) {
	/**
	 * @param int  $length Length.
	 * @param bool $special_chars Special chars.
	 * @param bool $extra_special Extra special chars.
	 * @return string
	 */
	function wp_generate_password( int $length = 12, bool $special_chars = true, bool $extra_special = false ): string {
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		if ( $special_chars ) {
			$chars .= '!@#$%^&*()';
		}
		if ( $extra_special ) {
			$chars .= '-_ []{}<>~`+=,.;:/?|';
		}

		$password = '';
		$max      = strlen( $chars ) - 1;
		for ( $i = 0; $i < $length; $i++ ) {
			$password .= $chars[ random_int( 0, $max ) ];
		}

		return $password;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * @param mixed $data Data.
	 * @return string|false
	 */
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}
