<?php

declare(strict_types=1);

use RecordingStudio\PluginSettings;
use RecordingStudio\SettingsForm;

function test_plugin_settings_normalizes_host_url(): void {
	$settings = PluginSettings::validate_and_merge(
		array(
			'host_base_url' => 'http://localhost:3000/',
			'client_id'     => 'client',
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
