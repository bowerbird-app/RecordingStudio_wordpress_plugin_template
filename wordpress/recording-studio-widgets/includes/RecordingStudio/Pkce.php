<?php

declare(strict_types=1);

namespace RecordingStudio;

final class Pkce {
	/** @var string */
	public string $verifier;

	/** @var string */
	public string $challenge;

	public function __construct( string $verifier, string $challenge ) {
		$this->verifier  = $verifier;
		$this->challenge = $challenge;
	}

	public static function generate(): self {
		$verifier = self::random_verifier();
		return new self( $verifier, self::s256_challenge( $verifier ) );
	}

	public static function s256_challenge( string $verifier ): string {
		$digest = hash( 'sha256', $verifier, true );
		// phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- PKCE S256 challenge, not obfuscation.
		return rtrim( strtr( base64_encode( $digest ), '+/', '-_' ), '=' );
	}

	private static function random_verifier(): string {
		$verifier = wp_generate_password( 64, false, false );
		if ( strlen( $verifier ) < 43 ) {
			$verifier = str_pad( $verifier, 43, 'a' );
		}

		return substr( $verifier, 0, 128 );
	}
}
