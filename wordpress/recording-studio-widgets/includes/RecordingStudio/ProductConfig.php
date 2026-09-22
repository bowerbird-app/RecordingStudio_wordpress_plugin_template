<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ProductConfig {
	public const NAME          = 'WordPress Plugin Demo';
	public const OAUTH_CONNECT = true;
	public const API_KEYS      = true;

	/**
	 * @return array{name: string, oauth_connect: bool, api_keys: bool}
	 */
	public static function all(): array {
		$config = array(
			'name'          => self::NAME,
			'oauth_connect' => self::OAUTH_CONNECT,
			'api_keys'      => self::API_KEYS,
		);

		if ( function_exists( 'apply_filters' ) ) {
			$filtered = apply_filters( 'recording_studio_product_config', $config );
			if ( is_array( $filtered ) ) {
				$config = $filtered;
			}
		}

		return array(
			'name'          => self::normalize_name( $config['name'] ?? null ),
			'oauth_connect' => self::normalize_flag( $config['oauth_connect'] ?? null, self::OAUTH_CONNECT ),
			'api_keys'      => self::normalize_flag( $config['api_keys'] ?? null, self::API_KEYS ),
		);
	}

	private static function normalize_name( $value ): string {
		if ( ! is_string( $value ) ) {
			return self::NAME;
		}

		$name = trim( $value );
		if ( '' === $name ) {
			return self::NAME;
		}

		return $name;
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
