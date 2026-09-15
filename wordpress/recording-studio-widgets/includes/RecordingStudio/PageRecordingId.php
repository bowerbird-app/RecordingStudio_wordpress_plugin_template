<?php

declare(strict_types=1);

namespace RecordingStudio;

final class PageRecordingId {
	private const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

	/** @var string */
	public $uuid;

	private function __construct( string $uuid ) {
		$this->uuid = $uuid;
	}

	/**
	 * @return self|EmbedResult
	 */
	public static function parse( string $raw ) {
		$trimmed = strtolower( trim( $raw ) );
		if ( '' === $trimmed || 1 !== preg_match( self::UUID_PATTERN, $trimmed ) ) {
			return EmbedResult::err(
				'invalid_page_recording_id',
				__( 'Enter a valid page recording id (UUID).', 'recording-studio-widget' )
			);
		}

		return new self( $trimmed );
	}

	public function to_string(): string {
		return $this->uuid;
	}
}
