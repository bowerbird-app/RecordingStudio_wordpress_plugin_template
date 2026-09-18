<?php

declare(strict_types=1);

use RecordingStudio\StudioClient;

function recording_studio_register_pages_route(): void {
	register_rest_route(
		'recording-studio/v1',
		'/pages',
		array(
			'methods'             => 'GET',
			'permission_callback' => static function (): bool {
				return current_user_can( 'edit_posts' );
			},
			'callback'            => static function () {
				return StudioClient::from_wp_options()->list_pages()->to_rest_response();
			},
		)
	);
}
