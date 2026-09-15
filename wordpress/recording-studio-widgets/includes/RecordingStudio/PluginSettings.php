<?php
/**
 * Server-side plugin configuration (Options API).
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

namespace RecordingStudio;

final class PluginSettings {
	public const OPTION_KEY = 'recording_studio_plugin_demo_settings';

	/** @var string */
	public $host_base_url;

	/** @var string */
	public $client_id;

	/** @var string */
	public $client_secret;

	/** @var string|null */
	public $token_url_override;

	public function __construct(
		string $host_base_url,
		string $client_id,
		string $client_secret,
		?string $token_url_override = null
	) {
		$this->host_base_url      = $host_base_url;
		$this->client_id          = $client_id;
		$this->client_secret      = $client_secret;
		$this->token_url_override = $token_url_override;
	}

	/**
	 * @param array<string, mixed> $stored Stored option value.
	 */
	public static function from_storage_array( array $stored ): self {
		return new self(
			self::normalize_base_url( (string) ( $stored['host_base_url'] ?? '' ) ),
			trim( (string) ( $stored['client_id'] ?? '' ) ),
			(string) ( $stored['client_secret'] ?? '' ),
			self::optional_url( $stored['token_url_override'] ?? null )
		);
	}

	/**
	 * @return array<string, string|null>
	 */
	public function to_storage_array(): array {
		return array(
			'host_base_url'      => $this->host_base_url,
			'client_id'          => $this->client_id,
			'client_secret'      => $this->client_secret,
			'token_url_override' => $this->token_url_override,
		);
	}

	public static function load(): ?self {
		$stored = get_option( self::OPTION_KEY, null );
		if ( ! is_array( $stored ) || empty( $stored ) ) {
			return null;
		}

		$settings = self::from_storage_array( $stored );
		if ( ! $settings->is_complete() ) {
			return null;
		}

		return $settings;
	}

	/**
	 * @param array<string, mixed> $incoming From SettingsForm or partial update.
	 */
	public static function validate_and_merge( array $incoming ): self {
		$existing = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $existing ) ) {
			$existing = array();
		}

		$host = array_key_exists( 'host_base_url', $incoming )
			? self::normalize_base_url( (string) $incoming['host_base_url'] )
			: self::normalize_base_url( (string) ( $existing['host_base_url'] ?? '' ) );

		$client_id = array_key_exists( 'client_id', $incoming )
			? trim( (string) $incoming['client_id'] )
			: trim( (string) ( $existing['client_id'] ?? '' ) );

		if ( array_key_exists( 'client_secret', $incoming ) ) {
			$client_secret = (string) $incoming['client_secret'];
			if ( '' === trim( $client_secret ) ) {
				$client_secret = (string) ( $existing['client_secret'] ?? '' );
			}
		} else {
			$client_secret = (string) ( $existing['client_secret'] ?? '' );
		}

		$token_override = null;
		if ( array_key_exists( 'token_url_override', $incoming ) ) {
			$token_override = self::optional_url( $incoming['token_url_override'] );
		} elseif ( array_key_exists( 'token_url_override', $existing ) ) {
			$token_override = self::optional_url( $existing['token_url_override'] );
		}

		return new self( $host, $client_id, $client_secret, $token_override );
	}

	public function is_complete(): bool {
		return '' !== $this->host_base_url
			&& '' !== $this->client_id
			&& '' !== $this->client_secret;
	}

	public function fingerprint(): string {
		return hash( 'sha256', $this->host_base_url . '|' . $this->client_id );
	}

	private static function normalize_base_url( string $url ): string {
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

	private static function optional_url( $value ): ?string {
		if ( null === $value ) {
			return null;
		}

		$trimmed = trim( (string) $value );
		if ( '' === $trimmed ) {
			return null;
		}

		return self::normalize_base_url( $trimmed );
	}
}
