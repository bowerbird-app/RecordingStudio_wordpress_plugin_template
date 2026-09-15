<?php
/**
 * Placeholder and error copy for block + settings.
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

namespace RecordingStudio;

final class Placeholder {
	public static function front_message(): string {
		return '<p ' . get_block_wrapper_attributes() . '>' . esc_html__(
			'Add a page recording id in the block settings to show the WordPress Plugin Demo embed.',
			'recording-studio-widget'
		) . '</p>';
	}

	public static function embed_error_markup( string $code, ?EmbedRequest $request = null ): string {
		return '<p ' . get_block_wrapper_attributes() . '>' . esc_html( self::embed_error_message( $code, $request ) ) . '</p>';
	}

	public static function settings_incomplete_message(): string {
		return __( 'Set the host URL and OAuth client credentials under Settings → WordPress Plugin Demo.', 'recording-studio-widget' );
	}

	public static function embed_error_message( string $code, ?EmbedRequest $request = null ): string {
		$editor = null !== $request && $request->is_editor_preview();

		switch ( $code ) {
			case 'invalid_page_recording_id':
				return __( 'Enter a valid page recording id (UUID).', 'recording-studio-widget' );
			case 'settings_incomplete':
				return self::settings_incomplete_message();
			case 'embed_not_found':
				if ( $editor ) {
					return __( 'No embed found for that recording id. Check the id or OAuth client scope.', 'recording-studio-widget' );
				}
				return __( 'This embed is not available right now.', 'recording-studio-widget' );
			case 'embed_unauthorized':
				return __( 'Host rejected the embed request. Check OAuth client credentials.', 'recording-studio-widget' );
			case 'payload_invalid':
				return __( 'Host returned an embed response this site could not use.', 'recording-studio-widget' );
			case 'embed_http_error':
			case 'embed_failed':
				if ( $editor ) {
					return __( 'Could not load a preview from the host. Check the host URL and try again.', 'recording-studio-widget' );
				}
				return __( 'Could not load the embed from the host.', 'recording-studio-widget' );
			default:
				return __( 'Something went wrong loading the WordPress Plugin Demo embed.', 'recording-studio-widget' );
		}
	}
}
