<?php
/**
 * BrowserPayload schema v1 (pass-through to browser / SDK).
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

namespace RecordingStudio;

final class BrowserPayload {
	public const SCHEMA_VERSION = 1;

	/** @var int */
	public $schema_version;

	/** @var string */
	public $html;

	/** @var array{theme: mixed, sizing: mixed} */
	public $configuration;

	/** @var array{minimum_version: string} */
	public $sdk;

	/** @var array<string, mixed> */
	private $raw;

	/**
	 * @param array{theme: mixed, sizing: mixed} $configuration
	 * @param array{minimum_version: string}     $sdk
	 * @param array<string, mixed>               $raw
	 */
	private function __construct(
		int $schema_version,
		string $html,
		array $configuration,
		array $sdk,
		array $raw
	) {
		$this->schema_version = $schema_version;
		$this->html           = $html;
		$this->configuration  = $configuration;
		$this->sdk            = $sdk;
		$this->raw            = $raw;
	}

	/**
	 * @param array<string, mixed> $decoded JSON from host.
	 * @return self|EmbedResult
	 */
	public static function from_json( array $decoded ) {
		if ( ! isset( $decoded['schema_version'] ) || self::SCHEMA_VERSION !== (int) $decoded['schema_version'] ) {
			return EmbedResult::err( 'payload_invalid', __( 'Embed response used an unsupported schema version.', 'recording-studio-widget' ) );
		}

		if ( ! isset( $decoded['html'] ) || ! is_string( $decoded['html'] ) ) {
			return EmbedResult::err( 'payload_invalid', __( 'Embed response was missing HTML.', 'recording-studio-widget' ) );
		}

		$configuration = $decoded['configuration'] ?? null;
		if ( ! is_array( $configuration ) || ! array_key_exists( 'theme', $configuration ) || ! array_key_exists( 'sizing', $configuration ) ) {
			return EmbedResult::err( 'payload_invalid', __( 'Embed response was missing widget configuration.', 'recording-studio-widget' ) );
		}

		$sdk = $decoded['sdk'] ?? null;
		if ( ! is_array( $sdk ) || empty( $sdk['minimum_version'] ) || ! is_string( $sdk['minimum_version'] ) ) {
			return EmbedResult::err( 'payload_invalid', __( 'Embed response was missing SDK version metadata.', 'recording-studio-widget' ) );
		}

		return new self(
			self::SCHEMA_VERSION,
			$decoded['html'],
			array(
				'theme'  => $configuration['theme'],
				'sizing' => $configuration['sizing'],
			),
			array( 'minimum_version' => $sdk['minimum_version'] ),
			$decoded
		);
	}

	/**
	 * @return array<string, mixed> Exact shape for wp_json_encode / REST.
	 */
	public function to_array(): array {
		return $this->raw;
	}
}
