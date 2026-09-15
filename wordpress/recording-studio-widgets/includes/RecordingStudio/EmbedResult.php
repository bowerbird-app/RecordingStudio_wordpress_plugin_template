<?php

declare(strict_types=1);

namespace RecordingStudio;

final class EmbedResult {
	/** @var BrowserPayload|null */
	private $payload;

	/** @var string|null */
	private $error_code;

	/** @var string|null */
	private $error_message;

	/** @var int|null */
	private $http_status;

	private function __construct(
		?BrowserPayload $payload,
		?string $error_code,
		?string $error_message,
		?int $http_status
	) {
		$this->payload       = $payload;
		$this->error_code    = $error_code;
		$this->error_message = $error_message;
		$this->http_status   = $http_status;
	}

	public static function ok( BrowserPayload $payload ): self {
		return new self( $payload, null, null, null );
	}

	public static function probe_ok(): self {
		return new self( null, null, null, null );
	}

	public static function err( string $code, string $message, ?int $http_status = null ): self {
		return new self( null, $code, $message, $http_status );
	}

	public function is_error(): bool {
		return null !== $this->error_code;
	}

	public function payload(): BrowserPayload {
		if ( $this->is_error() || null === $this->payload ) {
			throw new \LogicException( 'EmbedResult has no payload.' );
		}

		return $this->payload;
	}

	public function error_code(): string {
		if ( ! $this->is_error() ) {
			throw new \LogicException( 'EmbedResult has no error.' );
		}

		return $this->error_code;
	}

	public function error_message(): string {
		if ( ! $this->is_error() ) {
			throw new \LogicException( 'EmbedResult has no error.' );
		}

		return $this->error_message;
	}

	public function http_status(): ?int {
		return $this->http_status;
	}

	/**
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function to_rest_response() {
		if ( $this->is_error() ) {
			$status = $this->http_status ?? 400;
			return new \WP_Error(
				$this->error_code,
				$this->error_message,
				array( 'status' => $status )
			);
		}

		return new \WP_REST_Response( $this->payload()->to_array(), 200 );
	}
}
