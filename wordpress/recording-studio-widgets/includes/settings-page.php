<?php
/**
 * Admin settings UI for WordPress Plugin Demo host credentials.
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

use RecordingStudio\PluginSettings;
use RecordingStudio\SettingsForm;
use RecordingStudio\StudioClient;

/**
 * Renders the WordPress Plugin Demo settings screen.
 */
function recording_studio_plugin_demo_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = '';
	$stored = get_option( PluginSettings::OPTION_KEY, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	if ( isset( $_POST['rs_save_settings'] ) && check_admin_referer( 'rs_plugin_demo_settings' ) ) {
		$incoming = SettingsForm::read_post();
		$saved    = PluginSettings::validate_and_merge( $incoming );
		update_option( PluginSettings::OPTION_KEY, $saved->to_storage_array(), false );
		StudioClient::from_wp_options()->flush_token_cache();
		$stored = $saved->to_storage_array();
		$notice = 'saved';
	}

	if ( isset( $_POST['rs_test_connection'] ) && check_admin_referer( 'rs_plugin_demo_settings' ) ) {
		$incoming = SettingsForm::read_post();
		$saved    = PluginSettings::validate_and_merge( $incoming );
		update_option( PluginSettings::OPTION_KEY, $saved->to_storage_array(), false );
		StudioClient::from_wp_options()->flush_token_cache();
		$stored = $saved->to_storage_array();
		$probe  = StudioClient::from_wp_options()->probe_credentials();
		$notice = $probe->is_error() ? 'probe_failed' : 'probe_ok';
	}

	$host      = (string) ( $stored['host_base_url'] ?? '' );
	$client_id = (string) ( $stored['client_id'] ?? '' );
	$secret    = (string) ( $stored['client_secret'] ?? '' );
	$token_url = (string) ( $stored['token_url_override'] ?? '' );

	echo '<div class="wrap">';
	echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';

	if ( 'saved' === $notice ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'recording-studio-widget' ) . '</p></div>';
	}
	if ( 'probe_ok' === $notice ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Connection test succeeded. The host accepted the OAuth client credentials.', 'recording-studio-widget' ) . '</p></div>';
	}
	if ( 'probe_failed' === $notice ) {
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Connection test failed. Check the host URL and OAuth client credentials.', 'recording-studio-widget' ) . '</p></div>';
	}

	echo '<form method="post">';
	wp_nonce_field( 'rs_plugin_demo_settings' );
	echo '<table class="form-table" role="presentation">';
	echo '<tr><th scope="row"><label for="rs_host_base_url">' . esc_html__( 'Host base URL', 'recording-studio-widget' ) . '</label></th>';
	echo '<td><input name="rs_host_base_url" id="rs_host_base_url" type="url" class="regular-text" value="' . esc_attr( $host ) . '" placeholder="http://localhost:3000" /></td></tr>';
	echo '<tr><th scope="row"><label for="rs_client_id">' . esc_html__( 'OAuth client id', 'recording-studio-widget' ) . '</label></th>';
	echo '<td><input name="rs_client_id" id="rs_client_id" type="text" class="regular-text" value="' . esc_attr( $client_id ) . '" autocomplete="off" /></td></tr>';
	echo '<tr><th scope="row"><label for="rs_client_secret">' . esc_html__( 'OAuth client secret', 'recording-studio-widget' ) . '</label></th>';
	echo '<td><input name="rs_client_secret" id="rs_client_secret" type="password" class="regular-text" value="' . esc_attr( $secret ) . '" autocomplete="new-password" /></td></tr>';
	echo '<tr><th scope="row"><label for="rs_token_url_override">' . esc_html__( 'Token URL override (optional)', 'recording-studio-widget' ) . '</label></th>';
	echo '<td><input name="rs_token_url_override" id="rs_token_url_override" type="url" class="regular-text" value="' . esc_attr( $token_url ) . '" /></td></tr>';
	echo '</table>';
	echo '<p class="submit">';
	echo '<button type="submit" name="rs_save_settings" class="button button-primary">' . esc_html__( 'Save settings', 'recording-studio-widget' ) . '</button> ';
	echo '<button type="submit" name="rs_test_connection" class="button">' . esc_html__( 'Test connection', 'recording-studio-widget' ) . '</button>';
	echo '</p>';
	echo '</form>';
	echo '</div>';
}
