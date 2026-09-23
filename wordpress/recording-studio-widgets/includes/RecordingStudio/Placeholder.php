<?php

declare(strict_types=1);

namespace RecordingStudio;

final class Placeholder {
	public static function front_message(): string {
		$text = sprintf(
			/* translators: %s: product name. */
			__( 'Pick a page in the block settings to show the %s embed.', 'recording-studio-widget' ),
			ProductConfig::all()['name']
		);

		return '<p ' . get_block_wrapper_attributes() . '>' . esc_html( $text ) . '</p>';
	}

	public static function embed_error_markup( string $code, ?EmbedRequest $request = null ): string {
		return '<p ' . get_block_wrapper_attributes() . '>' . esc_html( self::embed_error_message( $code, $request ) ) . '</p>';
	}

	public static function settings_incomplete_message(): string {
		$config = ProductConfig::all();
		$name   = $config['name'];
		if ( $config['oauth_connect'] && $config['api_keys'] ) {
			return sprintf(
				/* translators: %s: product name. */
				__( 'Connect this site under Settings → %s, or add API keys under Advanced.', 'recording-studio-widget' ),
				$name
			);
		}
		if ( $config['api_keys'] ) {
			return sprintf(
				/* translators: %s: product name. */
				__( 'Add an API key and secret under Settings → %s.', 'recording-studio-widget' ),
				$name
			);
		}

		return sprintf(
			/* translators: %s: product name. */
			__( 'Connect this site under Settings → %s.', 'recording-studio-widget' ),
			$name
		);
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
				return sprintf(
					/* translators: %s: product name. */
					__( 'Something went wrong loading the %s embed.', 'recording-studio-widget' ),
					ProductConfig::all()['name']
				);
		}
	}
}
