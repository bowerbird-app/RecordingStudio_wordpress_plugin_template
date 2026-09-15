<?php
/**
 * SSR container + data-rs-payload for front.js mount.
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

namespace RecordingStudio;

final class BlockShell {
	public static function render( BlockAttributes $attrs, BrowserPayload $payload ): string {
		$encoded = wp_json_encode( $payload->to_array() );
		if ( false === $encoded ) {
			return Placeholder::embed_error_markup( 'payload_invalid' );
		}

		$wrapper = get_block_wrapper_attributes(
			array(
				'class'           => 'rs-wordpress-plugin-demo',
				'data-rs-widget'  => '1',
				'data-rs-payload' => $encoded,
			)
		);

		return '<div ' . $wrapper . '></div>';
	}
}
