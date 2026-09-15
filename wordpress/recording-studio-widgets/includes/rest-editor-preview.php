<?php
/**
 * Editor-only REST: preview embed payload (authenticated).
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

use RecordingStudio\EmbedRequest;
use RecordingStudio\PageRecordingId;
use RecordingStudio\StudioClient;

/**
 * Registers the editor preview REST route.
 */
function recording_studio_register_editor_preview_route(): void {
	register_rest_route(
		'recording-studio/v1',
		'/preview/(?P<page_recording_id>[0-9a-fA-F-]{36})',
		array(
			'methods'             => 'GET',
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
			'callback'            => static function ( \WP_REST_Request $request ) {
				$raw    = (string) $request->get_param( 'page_recording_id' );
				$parsed = PageRecordingId::parse( $raw );
				if ( $parsed instanceof \RecordingStudio\EmbedResult ) {
					return $parsed->to_rest_response();
				}

				$client = StudioClient::from_wp_options();
				$result = $client->embed_payload_for_page(
					$parsed,
					EmbedRequest::for_editor( $parsed )
				);

				return $result->to_rest_response();
			},
		)
	);
}
