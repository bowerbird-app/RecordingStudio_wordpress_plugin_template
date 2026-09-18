<?php

declare(strict_types=1);

use RecordingStudio\ConnectFlow;
use RecordingStudio\ConnectHandoff;
use RecordingStudio\ConnectStatus;
use RecordingStudio\HostUrls;
use RecordingStudio\PluginSettings;
use RecordingStudio\SettingsForm;
use RecordingStudio\SettingsPage;
use RecordingStudio\StudioClient;

function recording_studio_plugin_demo_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$notice = isset( $_GET['rs_notice'] ) ? sanitize_text_field( wp_unslash( (string) $_GET['rs_notice'] ) ) : '';
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

	ob_start();
	wp_nonce_field( 'rs_plugin_demo_settings' );
	$nonce_html = (string) ob_get_clean();

	// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- SettingsPage::markup escapes visible text and attributes.
	echo SettingsPage::markup(
		$stored,
		$notice,
		ConnectStatus::current(),
		admin_url( 'admin-post.php?action=' . ConnectFlow::START_ACTION ),
		admin_url( 'admin-post.php?action=' . ConnectFlow::DISCONNECT_ACTION ),
		$nonce_html
	);
	// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
}

function recording_studio_plugin_demo_settings_url( string $notice ): string {
	return add_query_arg(
		array(
			'page'      => 'recording-studio-plugin-demo',
			'rs_notice' => $notice,
		),
		admin_url( 'options-general.php' )
	);
}

function recording_studio_plugin_demo_require_manage_options(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'recording-studio-widget' ) );
	}
}

function recording_studio_plugin_demo_persist_posted_settings(): PluginSettings {
	$incoming = SettingsForm::read_post();
	$saved    = PluginSettings::validate_and_merge( $incoming );
	update_option( PluginSettings::OPTION_KEY, $saved->to_storage_array(), false );
	return $saved;
}

function recording_studio_plugin_demo_connect_start(): void {
	recording_studio_plugin_demo_require_manage_options();
	check_admin_referer( 'rs_plugin_demo_settings' );
	$settings = recording_studio_plugin_demo_persist_posted_settings();
	if ( ! $settings->can_start_connect() ) {
		wp_safe_redirect( recording_studio_plugin_demo_settings_url( 'connect_failed' ) );
		exit;
	}

	$url  = ConnectFlow::start( $settings, new HostUrls( $settings ) );
	$host = wp_parse_url( $settings->host_base_url, PHP_URL_HOST );
	if ( is_string( $host ) && '' !== $host ) {
		add_filter(
			'allowed_redirect_hosts',
			static function ( array $hosts ) use ( $host ): array {
				$hosts[] = $host;
				return $hosts;
			}
		);
	}

	$validated = wp_validate_redirect( $url, '' );
	if ( '' === $validated ) {
		wp_safe_redirect( recording_studio_plugin_demo_settings_url( 'connect_failed' ) );
		recording_studio_plugin_demo_halt();
	}

	ConnectHandoff::print( $validated );
	recording_studio_plugin_demo_halt();
}

function recording_studio_plugin_demo_halt(): void {
	if ( function_exists( 'rs_test_halt' ) ) {
		rs_test_halt();
	}
	exit;
}

function recording_studio_plugin_demo_connect_callback(): void {
	recording_studio_plugin_demo_require_manage_options();
	$query  = wp_unslash( $_GET ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$notice = ConnectFlow::finish( $query, StudioClient::from_wp_options() );
	wp_safe_redirect( recording_studio_plugin_demo_settings_url( $notice ) );
	exit;
}

function recording_studio_plugin_demo_disconnect(): void {
	recording_studio_plugin_demo_require_manage_options();
	check_admin_referer( 'rs_plugin_demo_settings' );
	$notice = ConnectFlow::disconnect();
	wp_safe_redirect( recording_studio_plugin_demo_settings_url( $notice ) );
	exit;
}
