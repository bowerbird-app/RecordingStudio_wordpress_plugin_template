<?php

declare(strict_types=1);

namespace RecordingStudio;

final class RegistrationOffer {
	/** @var bool */
	public bool $open;

	/** @var string */
	public string $url;

	public function __construct( bool $open, string $url ) {
		$this->open = $open;
		$this->url  = $url;
	}

	public static function hidden(): self {
		return new self( false, '' );
	}

	/**
	 * @param mixed $body
	 */
	public static function from_body( $body ): self {
		if ( ! is_array( $body ) || true !== ( $body['registration'] ?? null ) ) {
			return self::hidden();
		}

		$url = self::http_url( $body['registration_url'] ?? null );
		if ( '' === $url ) {
			return self::hidden();
		}

		return new self( true, $url );
	}

	public function visible_with( bool $product_register ): bool {
		return $product_register && $this->open && '' !== $this->url;
	}

	/**
	 * @param mixed $value
	 */
	private static function http_url( $value ): string {
		if ( ! is_string( $value ) ) {
			return '';
		}

		$url = trim( $value );
		if ( '' === $url || 1 === preg_match( '/\s/', $url ) ) {
			return '';
		}
		if ( 1 !== preg_match( '#\Ahttps?://#i', $url ) ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || ! is_string( $parts['host'] ) ) {
			return '';
		}

		return $url;
	}
}
