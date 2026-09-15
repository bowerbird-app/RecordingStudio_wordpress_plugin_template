<?php
/**
 * Embed intent with editor vs front context for error surfacing.
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

namespace RecordingStudio;

final class EmbedRequest {
	public const CONTEXT_EDITOR_PREVIEW = 'editor_preview';
	public const CONTEXT_SERVER_RENDER  = 'server_render';

	/** @var PageRecordingId */
	private $page_recording_id;

	/** @var string */
	private $context;

	private function __construct( PageRecordingId $page_recording_id, string $context ) {
		$this->page_recording_id = $page_recording_id;
		$this->context           = $context;
	}

	public static function for_editor( PageRecordingId $page_recording_id ): self {
		return new self( $page_recording_id, self::CONTEXT_EDITOR_PREVIEW );
	}

	public static function for_server_render( PageRecordingId $page_recording_id ): self {
		return new self( $page_recording_id, self::CONTEXT_SERVER_RENDER );
	}

	public function page_recording_id(): PageRecordingId {
		return $this->page_recording_id;
	}

	public function context(): string {
		return $this->context;
	}

	public function is_editor_preview(): bool {
		return self::CONTEXT_EDITOR_PREVIEW === $this->context;
	}
}
