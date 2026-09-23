<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ProductConfig {
	public const NAME                 = 'WP Template Demo';
	public const DESCRIPTION          = 'Shows a page from your studio.';
	public const OAUTH_CONNECT        = true;
	public const API_KEYS             = false;
	public const REGISTER             = true;
	public const LOGIN_BUTTON_TEXT    = 'Login';
	public const REGISTER_BUTTON_TEXT = 'Register';
	public const LOGO                 = 'build/brand/product-logo-red.jpg';
	public const EDITOR_SCRIPT_HANDLE = 'recording-studio-recording-studio-widget-editor-script';

	/**
	 * @return array{name: string, description: string, oauth_connect: bool, api_keys: bool, register: bool, login_button_text: string, register_button_text: string, logo: string}
	 */
	public static function all(): array {
		$config = array(
			'name'                 => self::NAME,
			'description'          => self::DESCRIPTION,
			'oauth_connect'        => self::OAUTH_CONNECT,
			'api_keys'             => self::API_KEYS,
			'register'             => self::REGISTER,
			'login_button_text'    => self::LOGIN_BUTTON_TEXT,
			'register_button_text' => self::REGISTER_BUTTON_TEXT,
			'logo'                 => self::LOGO,
		);

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'recording_studio_product_config', $config );
			if ( is_array( $filtered ) ) {
				$config = $filtered;
			}
		}

		return array(
			'name'                 => self::normalize_text( $config['name'] ?? null, self::NAME ),
			'description'          => self::normalize_text( $config['description'] ?? null, self::DESCRIPTION ),
			'oauth_connect'        => self::normalize_flag( $config['oauth_connect'] ?? null, self::OAUTH_CONNECT ),
			'api_keys'             => self::normalize_flag( $config['api_keys'] ?? null, self::API_KEYS ),
			'register'             => self::normalize_flag( $config['register'] ?? null, self::REGISTER ),
			'login_button_text'    => self::normalize_text( $config['login_button_text'] ?? null, self::LOGIN_BUTTON_TEXT ),
			'register_button_text' => self::normalize_text( $config['register_button_text'] ?? null, self::REGISTER_BUTTON_TEXT ),
			'logo'                 => self::normalize_logo( $config['logo'] ?? null ),
		);
	}

	public static function filter_block_metadata( array $metadata ): array {
		if ( ( $metadata['name'] ?? '' ) !== 'recording-studio/recording-studio-widget' ) {
			return $metadata;
		}

		$config                  = self::all();
		$metadata['title']       = $config['name'];
		$metadata['description'] = $config['description'];

		return $metadata;
	}

	public static function logo_url(): string {
		$relative = self::all()['logo'];
		$absolute = dirname( __DIR__, 2 ) . '/' . $relative;
		if ( ! is_file( $absolute ) ) {
			return '';
		}

		if ( ! function_exists( 'plugins_url' ) ) {
			return $relative;
		}

		return plugins_url( $relative, dirname( __DIR__, 2 ) . '/recording-studio-widget.php' );
	}

	/**
	 * @return array{name: string, description: string, logoUrl: string}
	 */
	public static function editor_config(): array {
		$config = self::all();

		return array(
			'name'        => $config['name'],
			'description' => $config['description'],
			'logoUrl'     => self::logo_url(),
		);
	}

	public static function enqueue_editor_config(): void {
		if ( ! function_exists( 'wp_add_inline_script' ) || ! function_exists( 'wp_json_encode' ) ) {
			return;
		}

		$json = wp_json_encode( self::editor_config() );
		if ( ! is_string( $json ) ) {
			return;
		}

		wp_add_inline_script(
			self::EDITOR_SCRIPT_HANDLE,
			'window.recordingStudioProductConfig = ' . $json . ';',
			'before'
		);
	}

	private static function normalize_text( $value, string $fallback ): string {
		if ( ! is_string( $value ) ) {
			return $fallback;
		}

		$text = trim( $value );
		if ( '' === $text ) {
			return $fallback;
		}

		return $text;
	}

	private static function normalize_flag( $value, bool $fallback ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}

		if ( is_int( $value ) ) {
			if ( 0 === $value ) {
				return false;
			}
			if ( 1 === $value ) {
				return true;
			}

			return $fallback;
		}

		if ( ! is_string( $value ) ) {
			return $fallback;
		}

		$normalized = strtolower( trim( $value ) );
		if ( in_array( $normalized, array( '0', 'false', 'no', 'off' ), true ) ) {
			return false;
		}
		if ( in_array( $normalized, array( '1', 'true', 'yes', 'on' ), true ) ) {
			return true;
		}

		return $fallback;
	}

	private static function normalize_logo( $value ): string {
		if ( ! is_string( $value ) || ! self::logo_path_allowed( trim( $value ) ) ) {
			return self::LOGO;
		}

		return trim( $value );
	}

	private static function logo_path_allowed( string $path ): bool {
		if ( '' === $path || false !== strpos( $path, '..' ) || false !== strpos( $path, '\\' ) ) {
			return false;
		}
		if ( '/' === substr( $path, 0, 1 ) || 1 === preg_match( '#\A[a-z][a-z0-9+.-]*:#i', $path ) ) {
			return false;
		}

		return 1 === preg_match( '/\.(jpe?g|png|gif|webp|svg)\z/i', $path );
	}
}
