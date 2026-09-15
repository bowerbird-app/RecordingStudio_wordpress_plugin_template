<?php
/**
 * Admin settings POST → array for PluginSettings::validate_and_merge.
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

namespace RecordingStudio;

final class SettingsForm {
	/**
	 * @return array<string, mixed>
	 */
	public static function read_post(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- Caller verifies nonce before read_post().
		return array(
			'host_base_url'      => isset( $_POST['rs_host_base_url'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['rs_host_base_url'] ) ) : '',
			'client_id'          => isset( $_POST['rs_client_id'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['rs_client_id'] ) ) : '',
			'client_secret'      => isset( $_POST['rs_client_secret'] ) ? sanitize_text_field( wp_unslash( (string) $_POST['rs_client_secret'] ) ) : '',
			'token_url_override' => isset( $_POST['rs_token_url_override'] ) ? esc_url_raw( wp_unslash( (string) $_POST['rs_token_url_override'] ) ) : '',
		);
		// phpcs:enable WordPress.Security.NonceVerification.Missing
	}
}
