<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ProductConfig {
	public const NAME                 = 'WordPress Plugin Demo';
	public const OAUTH_CONNECT        = true;
	public const API_KEYS             = true;
	public const LOGIN_BUTTON_TEXT    = 'Login';
	public const REGISTER_BUTTON_TEXT = 'Register';

	/**
	 * @return array{name: string, oauth_connect: bool, api_keys: bool, login_button_text: string, register_button_text: string}
	 */
	public static function all(): array {
		$config = array(
			'name'                 => self::NAME,
			'oauth_connect'        => self::OAUTH_CONNECT,
			'api_keys'             => self::API_KEYS,
			'login_button_text'    => self::LOGIN_BUTTON_TEXT,
			'register_button_text' => self::REGISTER_BUTTON_TEXT,
		);

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'recording_studio_product_config', $config );
			if ( is_array( $filtered ) ) {
				$config = $filtered;
			}
		}

		return array(
			'name'                 => self::normalize_text( $config['name'] ?? null, self::NAME ),
			'oauth_connect'        => self::normalize_flag( $config['oauth_connect'] ?? null, self::OAUTH_CONNECT ),
			'api_keys'             => self::normalize_flag( $config['api_keys'] ?? null, self::API_KEYS ),
			'login_button_text'    => self::normalize_text( $config['login_button_text'] ?? null, self::LOGIN_BUTTON_TEXT ),
			'register_button_text' => self::normalize_text( $config['register_button_text'] ?? null, self::REGISTER_BUTTON_TEXT ),
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
}
