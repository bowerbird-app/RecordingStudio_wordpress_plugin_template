<?php

declare(strict_types=1);

use RecordingStudio\CloudHost;
use RecordingStudio\PluginSettings;
use RecordingStudio\SettingsForm;

function test_cloud_host_defaults_normalize_host_url(): void {
	$GLOBALS['rs_test_filters'] = array();
	add_filter(
		CloudHost::HOST_FILTER,
		static function () {
			return 'http://localhost:3000/';
		}
	);

	if ( 'http://localhost:3000' !== CloudHost::host_base_url() ) {
		throw new RuntimeException( 'host_base_url was not normalized, got ' . CloudHost::host_base_url() );
	}

	$GLOBALS['rs_test_filters'] = array();
}

function test_plugin_settings_uses_baked_host_not_posted_host(): void {
	$GLOBALS['rs_test_filters'] = array();
	$settings                  = PluginSettings::validate_and_merge(
		array(
			'host_base_url' => 'https://evil.example',
			'client_id'     => 'posted-client',
			'api_key'       => 'api-key',
			'client_secret' => 'secret',
		)
	);

	if ( CloudHost::DEFAULT_HOST_BASE_URL !== $settings->host_base_url ) {
		throw new RuntimeException( 'posted host must not replace the baked host, got ' . $settings->host_base_url );
	}
	if ( CloudHost::DEFAULT_CLIENT_ID !== $settings->client_id ) {
		throw new RuntimeException( 'posted client id must not replace the baked client id, got ' . $settings->client_id );
	}
	if ( ! $settings->is_complete() ) {
		throw new RuntimeException( 'expected complete settings from Advanced keys plus baked host' );
	}
}

function test_connect_only_complete_with_blank_advanced_api_key(): void {
	$GLOBALS['rs_test_filters'] = array();
	$settings                   = PluginSettings::from_storage_array(
		array(
			'api_key'       => '',
			'client_secret' => '',
		)
	);
	$tokens = new \RecordingStudio\ConnectTokens( 'rsoauth_at_ok', 'rsoauth_rt_ok', time() + 3600 );

	if ( ! $settings->can_start_connect() ) {
		throw new RuntimeException( 'baked host + client id should start Connect' );
	}
	if ( $settings->has_api_keys() ) {
		throw new RuntimeException( 'blank Advanced API key should not count as API keys' );
	}
	if ( $settings->is_complete() ) {
		throw new RuntimeException( 'Connect-only without tokens should not be complete' );
	}
	if ( ! $settings->is_complete( $tokens ) ) {
		throw new RuntimeException( 'Connect-only with usable tokens should be complete' );
	}
	if ( '' !== $settings->advanced_api_key() ) {
		throw new RuntimeException( 'Connect-only should not invent an Advanced API key' );
	}
}

function test_advanced_only_complete_with_baked_connect_still_available(): void {
	$GLOBALS['rs_test_filters'] = array();
	$settings                   = PluginSettings::from_storage_array(
		array(
			'client_id'     => '',
			'api_key'       => 'advanced-api-key',
			'client_secret' => 'advanced-secret',
		)
	);

	if ( ! $settings->can_start_connect() ) {
		throw new RuntimeException( 'baked client id should still start Connect' );
	}
	if ( ! $settings->has_api_keys() ) {
		throw new RuntimeException( 'host + API key + secret should count as API keys' );
	}
	if ( ! $settings->is_complete() ) {
		throw new RuntimeException( 'Advanced-only should be complete without Connect tokens' );
	}
	if ( 'advanced-api-key' !== $settings->advanced_api_key() ) {
		throw new RuntimeException( 'Advanced API key should be the stored api_key, got ' . $settings->advanced_api_key() );
	}
}

function test_advanced_api_key_falls_back_to_shared_client_id(): void {
	$settings = PluginSettings::from_storage_array(
		array(
			'client_id'     => 'legacy-shared-id',
			'client_secret' => 'legacy-secret',
		)
	);

	if ( '' !== $settings->api_key ) {
		throw new RuntimeException( 'legacy storage should leave api_key empty' );
	}
	if ( 'legacy-shared-id' !== $settings->advanced_api_key() ) {
		throw new RuntimeException( 'empty api_key with a stored secret should read stored client_id as the Advanced API key' );
	}
	if ( ! $settings->has_api_keys() ) {
		throw new RuntimeException( 'legacy client_id + secret should still count as API keys' );
	}
	if ( ! $settings->is_complete() ) {
		throw new RuntimeException( 'legacy Advanced storage should stay complete' );
	}
}

function test_plugin_settings_blank_secret_clears_stored_secret(): void {
	update_option(
		PluginSettings::OPTION_KEY,
		array(
			'api_key'       => 'client',
			'client_secret' => 'existing-secret',
		)
	);

	$settings = PluginSettings::validate_and_merge(
		array(
			'client_secret' => '',
		)
	);

	if ( '' !== $settings->client_secret ) {
		throw new RuntimeException( 'blank Advanced secret should clear the stored secret, got ' . $settings->client_secret );
	}
	if ( $settings->has_api_keys() ) {
		throw new RuntimeException( 'cleared secret should not count as API keys' );
	}
}

function test_settings_form_blank_secret_clears_storage(): void {
	update_option(
		PluginSettings::OPTION_KEY,
		array(
			'client_id'     => 'client',
			'client_secret' => 'existing-secret',
		)
	);

	$_POST = array(
		'rs_api_key'       => 'client',
		'rs_client_secret' => '',
	);

	$settings = PluginSettings::validate_and_merge( SettingsForm::read_post() );
	if ( '' !== $settings->client_secret ) {
		throw new RuntimeException( 'empty Advanced submit should clear the stored secret' );
	}
	if ( array_key_exists( 'host_base_url', SettingsForm::read_post() ) || array_key_exists( 'client_id', SettingsForm::read_post() ) ) {
		throw new RuntimeException( 'Settings form should not read host or client id' );
	}
}

function test_plugin_settings_omitted_secret_keeps_stored_secret(): void {
	update_option(
		PluginSettings::OPTION_KEY,
		array(
			'api_key'       => 'client',
			'client_secret' => 'existing-secret',
		)
	);

	$settings = PluginSettings::validate_and_merge(
		array(
			'api_key' => 'client',
		)
	);

	if ( 'existing-secret' !== $settings->client_secret ) {
		throw new RuntimeException( 'omitted secret key should keep the stored secret' );
	}
}

function test_plugin_settings_storage_omits_host_and_client_id(): void {
	$settings = PluginSettings::validate_and_merge(
		array(
			'api_key'       => 'api-key',
			'client_secret' => 'secret',
		)
	);
	$stored = $settings->to_storage_array();
	if ( array_key_exists( 'host_base_url', $stored ) || array_key_exists( 'client_id', $stored ) || array_key_exists( 'token_url_override', $stored ) ) {
		throw new RuntimeException( 'storage should keep only Advanced keys' );
	}
	if ( 'api-key' !== $stored['api_key'] || 'secret' !== $stored['client_secret'] ) {
		throw new RuntimeException( 'storage should keep the Advanced keys' );
	}
}
