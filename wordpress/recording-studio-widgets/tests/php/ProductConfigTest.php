<?php

declare(strict_types=1);

use RecordingStudio\ConnectStatus;
use RecordingStudio\PluginSettings;
use RecordingStudio\ProductConfig;
use RecordingStudio\SettingsForm;
use RecordingStudio\SettingsPage;

function rs_clear_product_config_filter(): void {
	unset( $GLOBALS['rs_test_filters']['recording_studio_product_config'] );
}

function rs_product_config_markup( bool $connected, string $notice = '' ): string {
	return SettingsPage::markup(
		array(
			'client_id'     => 'legacy-shared-id',
			'client_secret' => 'super-secret',
		),
		$notice,
		new ConnectStatus( $connected ),
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start',
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect'
	);
}

function test_product_config_defaults_and_disconnected_markup(): void {
	rs_clear_product_config_filter();

	$expected = array(
		'name'          => 'WordPress Plugin Demo',
		'oauth_connect' => true,
		'api_keys'      => true,
	);
	if ( $expected !== ProductConfig::all() ) {
		throw new RuntimeException( 'ProductConfig::all() defaults mismatch' );
	}

	$html = rs_product_config_markup( false );
	if ( false === strpos( $html, 'WordPress Plugin Demo' ) ) {
		throw new RuntimeException( 'disconnected markup missing default name' );
	}
	if ( false === strpos( $html, 'Connect to Recording Studio' ) ) {
		throw new RuntimeException( 'disconnected markup missing Connect' );
	}
	if ( false === strpos( $html, '<summary>Advanced</summary>' ) ) {
		throw new RuntimeException( 'disconnected markup missing Advanced' );
	}
	if ( false === strpos( $html, 'name="rs_api_key"' ) ) {
		throw new RuntimeException( 'disconnected markup missing API key field' );
	}
	if ( false === strpos( $html, 'Save settings' ) ) {
		throw new RuntimeException( 'disconnected markup missing Save settings' );
	}
	if ( false === strpos( $html, 'Test connection' ) ) {
		throw new RuntimeException( 'disconnected markup missing Test connection' );
	}

	rs_clear_product_config_filter();
}

function test_product_config_oauth_only_hides_advanced(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['api_keys'] = false;
			return $config;
		}
	);

	$html = rs_product_config_markup( false );
	if ( false === strpos( $html, 'Connect to Recording Studio' ) ) {
		throw new RuntimeException( 'oauth-only markup missing Connect' );
	}
	if ( false !== strpos( $html, '<summary>Advanced</summary>' ) ) {
		throw new RuntimeException( 'oauth-only markup should hide Advanced' );
	}
	if ( false !== strpos( $html, 'name="rs_api_key"' ) ) {
		throw new RuntimeException( 'oauth-only markup should hide API key field' );
	}
	if ( false !== strpos( $html, 'Secret key' ) ) {
		throw new RuntimeException( 'oauth-only markup should hide Secret key' );
	}

	rs_clear_product_config_filter();
}

function test_product_config_api_only_hides_connect(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['oauth_connect'] = false;
			return $config;
		}
	);

	$html = rs_product_config_markup( true, 'connected' );
	if ( false !== strpos( $html, 'Connect to Recording Studio' ) ) {
		throw new RuntimeException( 'api-only markup should hide Connect' );
	}
	if ( false !== strpos( $html, 'Disconnect' ) ) {
		throw new RuntimeException( 'api-only markup should hide Disconnect' );
	}
	if ( false !== strpos( $html, 'Connect again' ) ) {
		throw new RuntimeException( 'api-only markup should hide Connect again' );
	}
	if ( false !== strpos( $html, 'This site is connected.' ) ) {
		throw new RuntimeException( 'api-only markup should hide connected notice' );
	}
	if ( false === strpos( $html, '<summary>Advanced</summary>' ) ) {
		throw new RuntimeException( 'api-only markup missing Advanced' );
	}
	if ( false === strpos( $html, 'name="rs_api_key"' ) ) {
		throw new RuntimeException( 'api-only markup missing API key field' );
	}

	rs_clear_product_config_filter();
}

function test_product_config_filter_overrides_name(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['name'] = 'Harbor Widgets';
			return $config;
		}
	);

	if ( 'Harbor Widgets' !== SettingsPage::title() ) {
		throw new RuntimeException( 'title should use the filtered name, got ' . SettingsPage::title() );
	}

	$html = rs_product_config_markup( false );
	if ( false === strpos( $html, 'Harbor Widgets' ) ) {
		throw new RuntimeException( 'markup missing filtered name' );
	}
	if ( false !== strpos( $html, 'WordPress Plugin Demo' ) ) {
		throw new RuntimeException( 'markup still shows the default name' );
	}

	rs_clear_product_config_filter();
}

function test_product_config_omits_posted_keys_when_api_keys_off(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['api_keys'] = false;
			return $config;
		}
	);

	$_POST['rs_api_key']       = 'posted-key';
	$_POST['rs_client_secret'] = 'posted-secret';

	$posted = SettingsForm::read_post();
	if ( array_key_exists( 'api_key', $posted ) || array_key_exists( 'client_secret', $posted ) ) {
		throw new RuntimeException( 'read_post must omit keys when api_keys is false' );
	}

	update_option(
		PluginSettings::OPTION_KEY,
		array(
			'api_key'       => 'kept-key',
			'client_secret' => 'kept-secret',
		)
	);

	$merged = PluginSettings::validate_and_merge( SettingsForm::read_post() );
	if ( 'kept-key' !== $merged->api_key ) {
		throw new RuntimeException( 'stored api key was overwritten, got ' . $merged->api_key );
	}
	if ( 'kept-secret' !== $merged->client_secret ) {
		throw new RuntimeException( 'stored secret was overwritten, got ' . $merged->client_secret );
	}

	unset( $_POST['rs_api_key'], $_POST['rs_client_secret'] );
	rs_clear_product_config_filter();
}

function test_product_config_empty_secret_clears_when_api_keys_on(): void {
	rs_clear_product_config_filter();

	update_option(
		PluginSettings::OPTION_KEY,
		array(
			'api_key'       => 'kept-key',
			'client_secret' => 'kept-secret',
		)
	);

	$_POST['rs_api_key']       = 'kept-key';
	$_POST['rs_client_secret'] = '';

	$merged = PluginSettings::validate_and_merge( SettingsForm::read_post() );
	if ( '' !== $merged->client_secret ) {
		throw new RuntimeException( 'empty secret should still clear when api_keys is true' );
	}

	unset( $_POST['rs_api_key'], $_POST['rs_client_secret'] );
	rs_clear_product_config_filter();
}
