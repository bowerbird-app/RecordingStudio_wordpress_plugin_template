<?php

declare(strict_types=1);

namespace RecordingStudio;

final class Placeholder {
	public static function front_message(): string {
		return '<p ' . get_block_wrapper_attributes() . '>' . esc_html__(
			'Pick a page in the block settings to show the WordPress Plugin Demo embed.',
			'recording-studio-widget'
		) . '</p>';
	}

	public static function embed_error_markup( string $code, ?EmbedRequest $request = null ): string {
		return '<p ' . get_block_wrapper_attributes() . '>' . esc_html( self::embed_error_message( $code, $request ) ) . '</p>';
	}

	public static function settings_incomplete_message(): string {
		return __( 'Connect this site under Settings → WordPress Plugin Demo, or add API keys under Advanced.', 'recording-studio-widget' );
	}

	public static function embed_error_message( string $code, ?EmbedRequest $request = null ): string {
		$editor = null !== $request && $request->is_editor_preview();

		if ( $editor ) {
			$shared = ConnectNotice::message( $code );
			if ( '' !== $shared ) {
				return $shared;
			}
		}

		switch ( $code ) {
			case ConnectNotice::INVALID_PAGE_RECORDING_ID:
				return ConnectNotice::message( ConnectNotice::INVALID_PAGE_RECORDING_ID );
			case 'settings_incomplete':
				return self::settings_incomplete_message();
			case ConnectNotice::EMBED_NOT_FOUND:
			case ConnectNotice::EMBED_UNAUTHORIZED:
				return __( 'This embed is not available right now.', 'recording-studio-widget' );
			case 'payload_invalid':
				return __( 'Host returned an embed this site could not use.', 'recording-studio-widget' );
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
