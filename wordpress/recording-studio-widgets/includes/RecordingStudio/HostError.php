<?php

declare(strict_types=1);

namespace RecordingStudio;

final class HostError {
	public const INVALID_CLIENT        = 'invalid_client';
	public const INVALID_GRANT         = 'invalid_grant';
	public const REDIRECT_URI_MISMATCH = 'redirect_uri_mismatch';
	public const INVALID_REDIRECT_URI  = 'invalid_redirect_uri';
	public const ACCESS_DENIED         = 'access_denied';

	/**
	 * @var array<string, string>
	 */
	private const NOTICE_BY_ERROR = array(
		self::INVALID_CLIENT        => ConnectNotice::INVALID_CLIENT,
		self::INVALID_GRANT         => ConnectNotice::EXPIRED_OR_USED_CODE,
		self::REDIRECT_URI_MISMATCH => ConnectNotice::REDIRECT_URI_MISMATCH,
		self::INVALID_REDIRECT_URI  => ConnectNotice::REDIRECT_URI_MISMATCH,
		self::ACCESS_DENIED         => ConnectNotice::CONNECT_DENIED,
	);

	/** @var string */
	public string $error;

	private function __construct( string $error ) {
		$this->error = $error;
	}

	/**
	 * @param mixed $payload Token or authorize JSON/query.
	 */
	public static function parse( $payload ): ?self {
		if ( ! is_array( $payload ) ) {
			return null;
		}

		if ( ! isset( $payload['error'] ) || ! is_string( $payload['error'] ) || '' === $payload['error'] ) {
			return null;
		}

		return new self( $payload['error'] );
	}

	public function notice_code(): string {
		return self::NOTICE_BY_ERROR[ $this->error ] ?? ConnectNotice::CONNECT_FAILED;
	}
}
