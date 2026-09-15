<?php
/**
 * StudioClient host I/O tests with injectable HTTP.
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

use RecordingStudio\ContractPaths;
use RecordingStudio\EmbedRequest;
use RecordingStudio\PageRecordingId;
use RecordingStudio\PluginSettings;
use RecordingStudio\StudioClient;

const RS_TEST_PAGE_UUID = '11111111-1111-4111-8111-111111111111';

function rs_seed_settings(): void {
	update_option(
		PluginSettings::OPTION_KEY,
		array(
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'demo-client',
			'client_secret' => 'demo-secret',
		)
	);
}

function test_studio_client_fetches_token_then_embed(): void {
	rs_seed_settings();
	$token_calls = 0;
	$embed_calls = 0;

	StudioClient::set_test_http_handlers(
		function ( string $url, array $fields ) use ( &$token_calls ) {
			++$token_calls;
			if ( false !== strpos( $url, ContractPaths::token_path() ) ) {
				return array(
					'status' => 200,
					'body'   => array(
						'access_token' => 'test-token',
						'expires_in'   => 3600,
					),
				);
			}
			return array( 'status' => 404, 'body' => null );
		},
		function ( string $url, array $headers ) use ( &$embed_calls ) {
			++$embed_calls;
			$expected = 'http://localhost:3000' . ContractPaths::embed_path( RS_TEST_PAGE_UUID );
			if ( $url !== $expected ) {
				throw new RuntimeException( 'unexpected embed URL: ' . $url );
			}
			if ( 'Bearer test-token' !== ( $headers['Authorization'] ?? '' ) ) {
				throw new RuntimeException( 'missing bearer on embed request' );
			}
			return array(
				'status' => 200,
				'body'   => array_merge(
					rs_sample_browser_payload(),
					array( 'html' => '<div>embed</div>' )
				),
			);
		}
	);

	$page = PageRecordingId::parse( RS_TEST_PAGE_UUID );
	if ( $page instanceof \RecordingStudio\EmbedResult ) {
		throw new RuntimeException( 'invalid test uuid' );
	}

	$client = StudioClient::from_wp_options();
	$result = $client->embed_payload_for_page( $page, EmbedRequest::for_server_render( $page ) );

	if ( $result->is_error() ) {
		throw new RuntimeException( 'embed failed: ' . $result->error_code() );
	}

	if ( 1 !== $token_calls || 1 !== $embed_calls ) {
		throw new RuntimeException( "expected single token+embed call, got {$token_calls}/{$embed_calls}" );
	}

	$second = $client->embed_payload_for_page( $page, EmbedRequest::for_server_render( $page ) );
	if ( $second->is_error() ) {
		throw new RuntimeException( 'cached embed failed' );
	}
	if ( 1 !== $token_calls ) {
		throw new RuntimeException( 'token should be cached on second embed' );
	}
	if ( 2 !== $embed_calls ) {
		throw new RuntimeException( 'embed should run again without new token' );
	}

	StudioClient::set_test_http_handlers( null, null );
}

function test_studio_client_maps_embed_404(): void {
	rs_seed_settings();
	StudioClient::set_test_http_handlers(
		static function () {
			return array(
				'status' => 200,
				'body'   => array(
					'access_token' => 't',
					'expires_in'   => 3600,
				),
			);
		},
		static function () {
			return array( 'status' => 404, 'body' => null );
		}
	);

	$page   = PageRecordingId::parse( RS_TEST_PAGE_UUID );
	$client = StudioClient::from_wp_options();
	$result = $client->embed_payload_for_page( $page, EmbedRequest::for_editor( $page ) );

	if ( ! $result->is_error() || 'embed_not_found' !== $result->error_code() ) {
		throw new RuntimeException( 'expected embed_not_found' );
	}

	StudioClient::set_test_http_handlers( null, null );
}

function test_probe_credentials_requires_token_ok(): void {
	rs_seed_settings();
	StudioClient::set_test_http_handlers(
		static function () {
			return array( 'status' => 401, 'body' => null );
		},
		static function () {
			return array( 'status' => 500, 'body' => null );
		}
	);

	$result = StudioClient::from_wp_options()->probe_credentials();
	if ( ! $result->is_error() ) {
		throw new RuntimeException( 'probe should fail on 401 token' );
	}

	StudioClient::set_test_http_handlers( null, null );
}
