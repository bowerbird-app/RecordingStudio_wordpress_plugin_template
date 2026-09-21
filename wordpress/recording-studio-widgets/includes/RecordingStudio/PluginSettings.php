<?php

declare(strict_types=1);

namespace RecordingStudio;

final class PluginSettings {
	public const OPTION_KEY = 'recording_studio_plugin_demo_settings';

	/** @var string */
	public string $host_base_url;

	/** @var string */
	public string $client_id;

	/** @var string */
	public string $api_key;

	/** @var string */
	public string $client_secret;

	/** @var string */
	private string $legacy_stored_client_id;

	public function __construct(
		string $host_base_url,
		string $client_id,
		string $api_key,
		string $client_secret,
		string $legacy_stored_client_id = ''
	) {
		$this->host_base_url           = $host_base_url;
		$this->client_id               = $client_id;
		$this->api_key                 = $api_key;
		$this->client_secret           = $client_secret;
		$this->legacy_stored_client_id = $legacy_stored_client_id;
	}

	/**
	 * @param array<string, mixed> $stored
	 */
	public static function from_storage_array( array $stored ): self {
		return new self(
			CloudHost::host_base_url(),
			CloudHost::client_id(),
			trim( (string) ( $stored['api_key'] ?? '' ) ),
			(string) ( $stored['client_secret'] ?? '' ),
			trim( (string) ( $stored['client_id'] ?? '' ) )
		);
	}

	/**
	 * @return array<string, string>
	 */
	public function to_storage_array(): array {
		return array(
			'api_key'       => $this->api_key,
			'client_secret' => $this->client_secret,
		);
	}

	public static function load(): self {
		$stored = get_option( self::OPTION_KEY, null );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return self::from_storage_array( $stored );
	}

	/**
	 * @param array<string, mixed> $incoming
	 */
	public static function validate_and_merge( array $incoming ): self {
		$existing = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$api_key = array_key_exists( 'api_key', $incoming )
			? trim( (string) $incoming['api_key'] )
			: trim( (string) ( $existing['api_key'] ?? '' ) );

		if ( array_key_exists( 'client_secret', $incoming ) ) {
			$client_secret = trim( (string) $incoming['client_secret'] );
		} else {
			$client_secret = (string) ( $existing['client_secret'] ?? '' );
		}

		$legacy_client_id = trim( (string) ( $existing['client_id'] ?? '' ) );

		return new self(
			CloudHost::host_base_url(),
			CloudHost::client_id(),
			$api_key,
			$client_secret,
			$legacy_client_id
		);
	}

	public function advanced_api_key(): string {
		if ( '' !== $this->api_key ) {
			return $this->api_key;
		}

		if ( '' !== $this->client_secret ) {
			return $this->legacy_stored_client_id;
		}

		return '';
	}

	public function has_api_keys(): bool {
		return '' !== $this->host_base_url
			&& '' !== $this->advanced_api_key()
			&& '' !== $this->client_secret;
	}

	public function can_start_connect(): bool {
		return CloudHost::ready();
	}

	public function is_complete( ?ConnectTokens $connect_tokens = null ): bool {
		return TokenPreference::resolve( $this, $connect_tokens )->is_complete();
	}

	public function fingerprint(): string {
		return hash( 'sha256', $this->host_base_url . '|' . $this->advanced_api_key() );
	}
}
