<?php

declare(strict_types=1);

use RecordingStudio\PluginSettings;
use RecordingStudio\SettingsForm;

function test_plugin_settings_normalizes_host_url(): void {
	$settings = PluginSettings::validate_and_merge(
		array(
			'host_base_url' => 'http://localhost:3000/',
			'client_id'     => 'client',
			'api_key'       => 'api-key',
			'client_secret' => 'secret',
		)
	);

	if ( 'http://localhost:3000' !== $settings->host_base_url ) {
		throw new RuntimeException( 'host_base_url was not normalized' );
	}

	if ( ! $settings->is_complete() ) {
		throw new RuntimeException( 'expected complete settings' );
	}
}

function test_connect_only_complete_with_blank_advanced_api_key(): void {
	$settings = PluginSettings::from_storage_array(
		array(
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'wp-public-client',
			'api_key'       => '',
			'client_secret' => '',
		)
	);
	$tokens = new \RecordingStudio\ConnectTokens( 'rsoauth_at_ok', 'rsoauth_rt_ok', time() + 3600 );

	if ( ! $settings->can_start_connect() ) {
		throw new RuntimeException( 'Connect-only host + OAuth client id should start Connect' );
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

function test_advanced_only_complete_with_blank_oauth_client_id(): void {
	$settings = PluginSettings::from_storage_array(
		array(
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => '',
			'api_key'       => 'advanced-api-key',
			'client_secret' => 'advanced-secret',
		)
	);

	if ( $settings->can_start_connect() ) {
		throw new RuntimeException( 'blank OAuth client id should not start Connect' );
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
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'legacy-shared-id',
			'client_secret' => 'legacy-secret',
		)
	);

	if ( '' !== $settings->api_key ) {
		throw new RuntimeException( 'legacy storage should leave api_key empty' );
	}
	if ( 'legacy-shared-id' !== $settings->advanced_api_key() ) {
		throw new RuntimeException( 'empty api_key with a stored secret should read client_id as the Advanced API key' );
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
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'client',
			'client_secret' => 'existing-secret',
		)
	);

	$settings = PluginSettings::validate_and_merge(
		array(
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'client',
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
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'client',
			'client_secret' => 'existing-secret',
		)
	);

	$_POST = array(
		'rs_host_base_url'      => 'http://localhost:3000',
		'rs_client_id'          => 'client',
		'rs_api_key'            => 'client',
		'rs_client_secret'      => '',
		'rs_token_url_override' => '',
	);

	$settings = PluginSettings::validate_and_merge( SettingsForm::read_post() );
	if ( '' !== $settings->client_secret ) {
		throw new RuntimeException( 'empty Advanced submit should clear the stored secret' );
	}
}

function test_plugin_settings_omitted_secret_keeps_stored_secret(): void {
	update_option(
		PluginSettings::OPTION_KEY,
		array(
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'client',
			'client_secret' => 'existing-secret',
		)
	);

	$settings = PluginSettings::validate_and_merge(
		array(
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'client',
		)
	);

	if ( 'existing-secret' !== $settings->client_secret ) {
		throw new RuntimeException( 'omitted secret key should keep the stored secret' );
	}
}
