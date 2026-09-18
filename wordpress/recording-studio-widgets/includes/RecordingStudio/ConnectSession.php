<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ConnectSession {
	public const TRANSIENT_KEY = 'recording_studio_plugin_demo_pkce';
	public const TTL_SECONDS   = 600;

	/** @var string */
	public string $verifier;

	/** @var string */
	public string $state;

	/** @var string */
	public string $redirect_uri;

	public function __construct( string $verifier, string $state, string $redirect_uri ) {
		$this->verifier     = $verifier;
		$this->state        = $state;
		$this->redirect_uri = $redirect_uri;
	}

	public function persist(): void {
		set_transient(
			self::TRANSIENT_KEY,
			array(
				'verifier'     => $this->verifier,
				'state'        => $this->state,
				'redirect_uri' => $this->redirect_uri,
			),
			self::TTL_SECONDS
		);
	}

	public static function load(): ?self {
		$stored = get_transient( self::TRANSIENT_KEY );
		if ( ! is_array( $stored ) ) {
			return null;
		}

		$verifier     = trim( (string) ( $stored['verifier'] ?? '' ) );
		$state        = trim( (string) ( $stored['state'] ?? '' ) );
		$redirect_uri = trim( (string) ( $stored['redirect_uri'] ?? '' ) );
		if ( '' === $verifier || '' === $state || '' === $redirect_uri ) {
			return null;
		}

		return new self( $verifier, $state, $redirect_uri );
	}

	public static function clear(): void {
		delete_transient( self::TRANSIENT_KEY );
	}
}
