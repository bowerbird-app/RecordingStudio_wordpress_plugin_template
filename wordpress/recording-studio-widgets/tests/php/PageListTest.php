<?php

declare(strict_types=1);

use RecordingStudio\ConnectTokens;
use RecordingStudio\ContractPaths;
use RecordingStudio\PageChoice;
use RecordingStudio\PageRecordingId;
use RecordingStudio\StudioClient;

function test_page_choice_parses_records_and_ignores_other_keys(): void {
	$pages = PageChoice::list_from_index_body(
		array(
			'resource' => 'pages',
			'type'     => 'index',
			'records'  => array(
				array(
					'id'    => RS_TEST_PAGE_UUID,
					'type'  => 'Page',
					'title' => 'Getting Started',
					'extra' => 'ignore-me',
				),
				array(
					'id'    => '22222222-2222-4222-8222-222222222222',
					'title' => 'Another page',
				),
				array(
					'type'  => 'Page',
					'title' => 'Missing id',
				),
			),
			'meta'     => array( 'count' => 2 ),
		)
	);

	if ( 2 !== count( $pages ) ) {
		throw new RuntimeException( 'expected two page choices, got ' . count( $pages ) );
	}
	if ( RS_TEST_PAGE_UUID !== $pages[0]->id || 'Getting Started' !== $pages[0]->title ) {
		throw new RuntimeException( 'first page choice mismatch' );
	}
	if ( '22222222-2222-4222-8222-222222222222' !== $pages[1]->id ) {
		throw new RuntimeException( 'second page id mismatch' );
	}

	$payload = $pages[0]->to_array();
	if ( array( 'id' => RS_TEST_PAGE_UUID, 'title' => 'Getting Started' ) !== $payload ) {
		throw new RuntimeException( 'to_array leaked extra keys' );
	}
}

function test_empty_page_list_still_allows_pasted_uuid(): void {
	$pages = PageChoice::list_from_records( array() );
	if ( array() !== $pages ) {
		throw new RuntimeException( 'empty records should parse to no pages' );
	}

	$parsed = PageRecordingId::parse( RS_TEST_PAGE_UUID );
	if ( $parsed instanceof \RecordingStudio\EmbedResult ) {
		throw new RuntimeException( 'pasted UUID should stay valid when the list is empty' );
	}
	if ( RS_TEST_PAGE_UUID !== $parsed->to_string() ) {
		throw new RuntimeException( 'pasted UUID did not round-trip' );
	}
}

function test_studio_client_lists_pages_with_connect_bearer(): void {
	rs_seed_connect_settings_without_secret();
	$tokens = new ConnectTokens( 'rsoauth_at_list', 'rsoauth_rt_list', time() + 3600 );
	$tokens->save();

	$list_url  = '';
	$list_auth = '';
	StudioClient::set_test_http_handlers(
		static function () {
			throw new RuntimeException( 'page list should not request a new token when Connect tokens are fresh' );
		},
		function ( string $url, array $headers ) use ( &$list_url, &$list_auth ) {
			$list_url  = $url;
			$list_auth = $headers['Authorization'] ?? '';
			return array(
				'status' => 200,
				'body'   => array(
					'resource' => 'pages',
					'records'  => array(
						array(
							'id'    => RS_TEST_PAGE_UUID,
							'type'  => 'Page',
							'title' => 'Getting Started',
						),
					),
				),
			);
		}
	);

	$result = StudioClient::from_wp_options()->list_pages();
	if ( $result->is_error() ) {
		throw new RuntimeException( 'list_pages failed: ' . $result->error_code() );
	}

	$pages = $result->pages();
	if ( 1 !== count( $pages ) || 'Getting Started' !== $pages[0]->title ) {
		throw new RuntimeException( 'list_pages did not return Getting Started' );
	}

	$expected = 'http://localhost:3000' . ContractPaths::pages_path();
	if ( $expected !== $list_url ) {
		throw new RuntimeException( 'page list used the wrong URL: ' . $list_url );
	}
	if ( 'Bearer rsoauth_at_list' !== $list_auth ) {
		throw new RuntimeException( 'page list did not use the Connect bearer' );
	}
	if ( '/recording_studio_api/apis/wp_plugin_demo/v1/pages' !== ContractPaths::pages_path() ) {
		throw new RuntimeException( 'pages_path mismatch: ' . ContractPaths::pages_path() );
	}

	StudioClient::set_test_http_handlers( null, null );
}

function test_studio_client_lists_pages_with_api_key_bearer_when_disconnected(): void {
	rs_seed_settings();
	StudioClient::from_wp_options()->flush_token_cache();

	$grant = '';
	$auth  = '';
	StudioClient::set_test_http_handlers(
		function ( string $url, array $fields ) use ( &$grant ) {
			$grant = (string) ( $fields['grant_type'] ?? '' );
			if ( false === strpos( $url, ContractPaths::token_path() ) ) {
				throw new RuntimeException( 'api key list used the wrong token path' );
			}
			return array(
				'status' => 200,
				'body'   => array(
					'access_token' => 'api-key-list-token',
					'expires_in'   => 3600,
				),
			);
		},
		function ( string $url, array $headers ) use ( &$auth ) {
			$auth = $headers['Authorization'] ?? '';
			return array(
				'status' => 200,
				'body'   => array( 'records' => array() ),
			);
		}
	);

	$result = StudioClient::from_wp_options()->list_pages();
	if ( $result->is_error() ) {
		throw new RuntimeException( 'disconnected list_pages failed: ' . $result->error_code() );
	}
	if ( array() !== $result->pages() ) {
		throw new RuntimeException( 'expected an empty page list' );
	}
	if ( ContractPaths::TOKEN_GRANT !== $grant ) {
		throw new RuntimeException( 'disconnected list should use client_credentials' );
	}
	if ( 'Bearer api-key-list-token' !== $auth ) {
		throw new RuntimeException( 'disconnected list used the wrong bearer' );
	}

	$parsed = PageRecordingId::parse( RS_TEST_PAGE_UUID );
	if ( $parsed instanceof \RecordingStudio\EmbedResult ) {
		throw new RuntimeException( 'paste UUID should work after an empty list' );
	}

	StudioClient::set_test_http_handlers( null, null );
}

function test_pages_path_is_named_prefix_v1_pages(): void {
	if ( ContractPaths::NAMED_PREFIX . '/v1/pages' !== ContractPaths::pages_path() ) {
		throw new RuntimeException( 'pages_path should be NAMED_PREFIX/v1/pages' );
	}
}