<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ConnectTokens {
	public const OPTION_KEY           = 'recording_studio_plugin_demo_connect_tokens';
	private const EXPIRY_SKEW_SECONDS = 60;

	/** @var string */
	public string $access_token;

	/** @var string */
	public string $refresh_token;

	/** @var int */
	public int $expires_at;

	public function __construct( string $access_token, string $refresh_token, int $expires_at ) {
		$this->access_token  = $access_token;
		$this->refresh_token = $refresh_token;
		$this->expires_at    = $expires_at;
	}

	/**
	 * @param array<string, mixed> $body Token endpoint JSON.
	 */
	public static function parse_response( array $body ): ?self {
		if ( empty( $body['access_token'] ) || ! is_string( $body['access_token'] ) ) {
			return null;
		}

		$expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;
		$expires_in = max( 60, $expires_in );
		$refresh    = ( isset( $body['refresh_token'] ) && is_string( $body['refresh_token'] ) )
			? $body['refresh_token']
			: '';

		return new self( $body['access_token'], $refresh, time() + $expires_in );
	}

	public function is_fresh( int $now ): bool {
		return $now < ( $this->expires_at - self::EXPIRY_SKEW_SECONDS );
	}

	public function usable(): bool {
		if ( '' === $this->access_token ) {
			return false;
		}

		if ( $this->is_fresh( time() ) ) {
			return true;
		}

		return '' !== $this->refresh_token;
	}

	public function save(): void {
		update_option(
			self::OPTION_KEY,
			array(
				'access_token'  => $this->access_token,
				'refresh_token' => $this->refresh_token,
				'expires_at'    => $this->expires_at,
			),
			false
		);
	}

	public static function load(): ?self {
		$stored = get_option( self::OPTION_KEY, null );
		if ( ! is_array( $stored ) || empty( $stored['access_token'] ) ) {
			return null;
		}

		return new self(
			(string) $stored['access_token'],
			(string) ( $stored['refresh_token'] ?? '' ),
			(int) ( $stored['expires_at'] ?? 0 )
		);
	}

	public static function clear(): void {
		delete_option( self::OPTION_KEY );
	}

	public static function connected(): bool {
		return null !== self::load();
	}
}
