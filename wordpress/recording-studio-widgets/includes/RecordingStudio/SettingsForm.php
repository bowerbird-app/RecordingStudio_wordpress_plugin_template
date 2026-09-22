<?php

declare(strict_types=1);

namespace RecordingStudio;

final class SettingsForm {
	/**
	 * @return array<string, mixed>
	 */
	public static function read_post(): array {
		if ( ! ProductConfig::all()['api_keys'] ) {
			return array();
		}

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Caller verifies nonce before read_post().
		return array(
			'api_key'       => isset( $_POST['rs_api_key'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['rs_api_key'] ) ) : '',
			'client_secret' => isset( $_POST['rs_client_secret'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['rs_client_secret'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
