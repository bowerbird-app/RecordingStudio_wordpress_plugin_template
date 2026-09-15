<?php
/**
 * Block attributes from block.json (pageRecordingId).
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

namespace RecordingStudio;

final class BlockAttributes {
	/** @var string */
	public $page_recording_id;

	public function __construct( string $page_recording_id ) {
		$this->page_recording_id = $page_recording_id;
	}

	/**
	 * @param array<string, mixed> $attributes From render callback.
	 */
	public static function from_block_props( array $attributes ): self {
		$id = isset( $attributes['pageRecordingId'] ) ? (string) $attributes['pageRecordingId'] : '';

		return new self( trim( $id ) );
	}

	public function has_page_recording_id(): bool {
		return '' !== $this->page_recording_id;
	}
}
