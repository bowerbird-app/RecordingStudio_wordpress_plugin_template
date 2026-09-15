<?php

declare(strict_types=1);

namespace RecordingStudio;

final class CachedAccessToken {
	private const EXPIRY_SKEW_SECONDS = 60;

	/** @var string */
	public $access_token;

	/** @var int */
	public $expires_at;

	public function __construct( string $access_token, int $expires_at ) {
		$this->access_token = $access_token;
		$this->expires_at   = $expires_at;
	}

	public function is_valid( int $now ): bool {
		return $now < ( $this->expires_at - self::EXPIRY_SKEW_SECONDS );
	}

	public static function load( string $cache_key ): ?self {
		$cached = get_transient( $cache_key );
		if ( ! is_array( $cached ) ) {
			return null;
		}

		if ( empty( $cached['access_token'] ) || empty( $cached['expires_at'] ) ) {
			return null;
		}

		return new self( (string) $cached['access_token'], (int) $cached['expires_at'] );
	}

	public function save( string $cache_key ): void {
		$ttl = max( 60, $this->expires_at - time() );
		set_transient(
			$cache_key,
			array(
				'access_token' => $this->access_token,
				'expires_at'   => $this->expires_at,
			),
			$ttl
		);
	}

	public static function clear( string $cache_key ): void {
		delete_transient( $cache_key );
	}
}
