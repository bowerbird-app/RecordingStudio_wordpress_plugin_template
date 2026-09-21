<?php

declare(strict_types=1);

namespace RecordingStudio;

final class CloudHost {
	public const DEFAULT_HOST_BASE_URL = 'http://localhost:3000';
	public const DEFAULT_CLIENT_ID     = 'rsoauth_id_wordpress';
	public const HOST_CONSTANT         = 'RECORDING_STUDIO_HOST_BASE_URL';
	public const CLIENT_CONSTANT       = 'RECORDING_STUDIO_CLIENT_ID';
	public const HOST_FILTER           = 'recording_studio_host_base_url';
	public const CLIENT_FILTER         = 'recording_studio_client_id';

	public static function host_base_url(): string {
		$value = self::constant_or_default( self::HOST_CONSTANT, self::DEFAULT_HOST_BASE_URL );
		if ( function_exists( 'apply_filters' ) ) {
			$value = (string) apply_filters( self::HOST_FILTER, $value );
		}

		return self::normalize_base_url( $value );
	}

	public static function client_id(): string {
		$value = self::constant_or_default( self::CLIENT_CONSTANT, self::DEFAULT_CLIENT_ID );
		if ( function_exists( 'apply_filters' ) ) {
			$value = (string) apply_filters( self::CLIENT_FILTER, $value );
		}

		return trim( $value );
	}

	public static function ready(): bool {
		return '' !== self::host_base_url() && '' !== self::client_id();
	}

	public static function normalize_base_url( string $url ): string {
		$url = trim( $url );
		if ( '' === $url ) {
			return '';
		}

		$url = rtrim( $url, '/' );
		if ( ! preg_match( '#^https?://#i', $url ) ) {
			$url = 'https://' . $url;
		}

		return rtrim( $url, '/' );
	}

	private static function constant_or_default( string $name, string $default ): string {
		if ( defined( $name ) ) {
			$value = trim( (string) constant( $name ) );
			if ( '' !== $value ) {
				return $value;
			}
		}

		return $default;
	}
}
