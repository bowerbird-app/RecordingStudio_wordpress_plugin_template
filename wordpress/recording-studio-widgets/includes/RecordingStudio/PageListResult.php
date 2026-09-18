<?php

declare(strict_types=1);

namespace RecordingStudio;

final class PageListResult {
	/**
	 * @var list<PageChoice>
	 */
	private array $pages;

	/** @var string|null */
	private ?string $error_code;

	/** @var string|null */
	private ?string $error_message;

	/** @var int|null */
	private ?int $http_status;

	/**
	 * @param list<PageChoice> $pages Page choices.
	 */
	private function __construct(
		array $pages,
		?string $error_code,
		?string $error_message,
		?int $http_status
	) {
		$this->pages         = $pages;
		$this->error_code    = $error_code;
		$this->error_message = $error_message;
		$this->http_status   = $http_status;
	}

	/**
	 * @param list<PageChoice> $pages Page choices.
	 */
	public static function ok( array $pages ): self {
		return new self( $pages, null, null, null );
	}

	public static function err( string $code, string $message, ?int $http_status = null ): self {
		return new self( array(), $code, $message, $http_status );
	}

	public static function from_embed_error( EmbedResult $error ): self {
		return self::err( $error->error_code(), $error->error_message(), $error->http_status() );
	}

	public function is_error(): bool {
		return null !== $this->error_code;
	}

	/**
	 * @return list<PageChoice>
	 * @throws \LogicException When the result is an error.
	 */
	public function pages(): array {
		if ( $this->is_error() ) {
			throw new \LogicException( 'PageListResult has no pages.' );
		}

		return $this->pages;
	}

	public function error_code(): string {
		if ( ! $this->is_error() ) {
			throw new \LogicException( 'PageListResult has no error.' );
		}

		return $this->error_code;
	}

	public function error_message(): string {
		if ( ! $this->is_error() ) {
			throw new \LogicException( 'PageListResult has no error.' );
		}

		return $this->error_message;
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

		$pages = array();
		foreach ( $this->pages as $page ) {
			$pages[] = $page->to_array();
		}

		return new \WP_REST_Response( array( 'pages' => $pages ), 200 );
	}
}
