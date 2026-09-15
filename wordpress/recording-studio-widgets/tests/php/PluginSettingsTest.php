<?php
/**
 * PluginSettings validation tests.
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

use RecordingStudio\PluginSettings;

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

function test_plugin_settings_merge_keeps_secret_when_blank_incoming(): void {
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

	if ( 'existing-secret' !== $settings->client_secret ) {
		throw new RuntimeException( 'client_secret should merge from stored option' );
	}
}
