<?php

declare(strict_types=1);

use RecordingStudio\ConnectFlow;
use RecordingStudio\ConnectSession;
use RecordingStudio\ConnectTokens;
use RecordingStudio\ContractPaths;
use RecordingStudio\EmbedRequest;
use RecordingStudio\HostUrls;
use RecordingStudio\PageRecordingId;
use RecordingStudio\Pkce;
use RecordingStudio\PluginSettings;
use RecordingStudio\SettingsPage;
use RecordingStudio\StudioClient;

function rs_seed_connect_settings_without_secret(): void {
	ConnectTokens::clear();
	ConnectSession::clear();
	update_option(
		PluginSettings::OPTION_KEY,
		array(
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'wp-public-client',
			'client_secret' => '',
		)
	);
}

function test_pkce_challenge_is_s256_of_verifier(): void {
	$verifier  = str_repeat( 'a', 43 );
	$challenge = Pkce::s256_challenge( $verifier );
	$expected  = rtrim( strtr( base64_encode( hash( 'sha256', $verifier, true ) ), '+/', '-_' ), '=' );

	if ( $expected !== $challenge ) {
		throw new RuntimeException( 'PKCE challenge was not S256 of the verifier' );
	}

	$generated = Pkce::generate();
	if ( strlen( $generated->verifier ) < 43 || strlen( $generated->verifier ) > 128 ) {
		throw new RuntimeException( 'PKCE verifier length is out of range' );
	}
	if ( $generated->challenge !== Pkce::s256_challenge( $generated->verifier ) ) {
		throw new RuntimeException( 'generated PKCE challenge did not match verifier' );
	}
}

function test_connect_start_builds_authorize_url_and_keeps_verifier_in_transient(): void {
	rs_seed_connect_settings_without_secret();
	$settings = PluginSettings::load();
	if ( null === $settings ) {
		throw new RuntimeException( 'expected stored settings' );
	}

	$url   = ConnectFlow::start( $settings, new HostUrls( $settings ) );
	$parts = parse_url( $url );
	$query = array();
	parse_str( (string) ( $parts['query'] ?? '' ), $query );

	if ( ( $parts['path'] ?? '' ) !== ContractPaths::authorize_path() ) {
		throw new RuntimeException( 'authorize path mismatch: ' . ( $parts['path'] ?? '' ) );
	}
	if ( 'code' !== ( $query['response_type'] ?? '' ) ) {
		throw new RuntimeException( 'missing response_type=code' );
	}
	if ( 'wp-public-client' !== ( $query['client_id'] ?? '' ) ) {
		throw new RuntimeException( 'missing client_id' );
	}
	if ( 'S256' !== ( $query['code_challenge_method'] ?? '' ) ) {
		throw new RuntimeException( 'missing S256 method' );
	}
	$redirect = ConnectFlow::callback_uri();
	if ( $redirect !== ( $query['redirect_uri'] ?? '' ) ) {
		throw new RuntimeException( 'redirect_uri mismatch' );
	}
	if ( empty( $query['state'] ) || empty( $query['code_challenge'] ) ) {
		throw new RuntimeException( 'missing state or code_challenge' );
	}
	if ( false !== strpos( $url, 'code_verifier' ) || false !== strpos( $url, 'client_secret' ) ) {
		throw new RuntimeException( 'verifier or secret leaked into authorize URL' );
	}

	$session = ConnectSession::load();
	if ( null === $session ) {
		throw new RuntimeException( 'PKCE session was not stored' );
	}
	if ( $session->verifier === ( $query['code_challenge'] ?? '' ) ) {
		throw new RuntimeException( 'verifier was used as the challenge' );
	}
	if ( $session->state !== $query['state'] ) {
		throw new RuntimeException( 'stored state did not match authorize URL' );
	}
	if ( $session->redirect_uri !== $redirect ) {
		throw new RuntimeException( 'stored redirect_uri did not match callback' );
	}
	if ( $query['code_challenge'] !== Pkce::s256_challenge( $session->verifier ) ) {
		throw new RuntimeException( 'authorize challenge was not S256 of stored verifier' );
	}
}

function test_connect_callback_stores_tokens_and_embed_uses_connect_bearer(): void {
	rs_seed_connect_settings_without_secret();
	$settings = PluginSettings::load();
	$url      = ConnectFlow::start( $settings, new HostUrls( $settings ) );
	$parts    = parse_url( $url );
	$query    = array();
	parse_str( (string) ( $parts['query'] ?? '' ), $query );
	$session = ConnectSession::load();
	if ( null === $session ) {
		throw new RuntimeException( 'missing PKCE session' );
	}

	$token_posts = array();
	$embed_auth  = '';
	StudioClient::set_test_http_handlers(
		function ( string $token_url, array $fields ) use ( &$token_posts, $session ) {
			$token_posts[] = array( $token_url, $fields );
			if ( false === strpos( $token_url, ContractPaths::connect_token_path() ) ) {
				throw new RuntimeException( 'connect exchange used the wrong token URL: ' . $token_url );
			}
			if ( ContractPaths::CONNECT_GRANT !== ( $fields['grant_type'] ?? '' ) ) {
				throw new RuntimeException( 'expected authorization_code grant' );
			}
			if ( $session->verifier !== ( $fields['code_verifier'] ?? '' ) ) {
				throw new RuntimeException( 'code_verifier was not the stored verifier' );
			}
			if ( ! empty( $fields['client_secret'] ) ) {
				throw new RuntimeException( 'connect exchange sent a client secret' );
			}
			return array(
				'status' => 200,
				'body'   => array(
					'access_token'  => 'rsoauth_at_connect_token',
					'refresh_token' => 'rsoauth_rt_connect_refresh',
					'expires_in'    => 3600,
					'token_type'    => 'Bearer',
				),
			);
		},
		function ( string $embed_url, array $headers ) use ( &$embed_auth ) {
			$embed_auth = $headers['Authorization'] ?? '';
			return array(
				'status' => 200,
				'body'   => array_merge(
					rs_sample_browser_payload(),
					array( 'html' => '<div>connect-embed</div>' )
				),
			);
		}
	);

	$notice = ConnectFlow::finish(
		array(
			'code'  => 'auth-code-1',
			'state' => $query['state'],
		),
		StudioClient::from_wp_options()
	);
	if ( 'connected' !== $notice ) {
		throw new RuntimeException( 'expected connected notice, got ' . $notice );
	}
	if ( null !== ConnectSession::load() ) {
		throw new RuntimeException( 'PKCE session should be cleared after callback' );
	}

	$stored = ConnectTokens::load();
	if ( null === $stored || 'rsoauth_at_connect_token' !== $stored->access_token ) {
		throw new RuntimeException( 'connect tokens were not stored' );
	}

	$page = PageRecordingId::parse( RS_TEST_PAGE_UUID );
	if ( $page instanceof \RecordingStudio\EmbedResult ) {
		throw new RuntimeException( 'invalid test uuid' );
	}
	$result = StudioClient::from_wp_options()->embed_payload_for_page( $page, EmbedRequest::for_server_render( $page ) );
	if ( $result->is_error() ) {
		throw new RuntimeException( 'embed failed: ' . $result->error_code() );
	}
	if ( 'Bearer rsoauth_at_connect_token' !== $embed_auth ) {
		throw new RuntimeException( 'embed did not use the Connect bearer' );
	}
	if ( 1 !== count( $token_posts ) ) {
		throw new RuntimeException( 'embed should not request client_credentials when Connect tokens exist' );
	}

	StudioClient::set_test_http_handlers( null, null );
}

function test_connect_denied_clears_session_without_tokens(): void {
	rs_seed_connect_settings_without_secret();
	$settings = PluginSettings::load();
	ConnectFlow::start( $settings, new HostUrls( $settings ) );

	$notice = ConnectFlow::finish(
		array( 'error' => 'access_denied', 'state' => 'ignored' ),
		StudioClient::from_wp_options()
	);
	if ( 'connect_denied' !== $notice ) {
		throw new RuntimeException( 'expected connect_denied' );
	}
	if ( null !== ConnectSession::load() ) {
		throw new RuntimeException( 'session should clear on access_denied' );
	}
	if ( null !== ConnectTokens::load() ) {
		throw new RuntimeException( 'tokens should not be stored on access_denied' );
	}
}

function test_api_key_fallback_hits_named_token_path(): void {
	rs_seed_settings();
	$token_url_seen = '';
	StudioClient::set_test_http_handlers(
		function ( string $url, array $fields ) use ( &$token_url_seen ) {
			$token_url_seen = $url;
			if ( ContractPaths::TOKEN_GRANT !== ( $fields['grant_type'] ?? '' ) ) {
				throw new RuntimeException( 'expected client_credentials grant' );
			}
			if ( empty( $fields['client_secret'] ) ) {
				throw new RuntimeException( 'client_credentials should send the secret' );
			}
			return array(
				'status' => 200,
				'body'   => array(
					'access_token' => 'api-key-token',
					'expires_in'   => 3600,
				),
			);
		},
		static function () {
			return array(
				'status' => 200,
				'body'   => array_merge(
					rs_sample_browser_payload(),
					array( 'html' => '<div>keys</div>' )
				),
			);
		}
	);

	$page = PageRecordingId::parse( RS_TEST_PAGE_UUID );
	$result = StudioClient::from_wp_options()->embed_payload_for_page( $page, EmbedRequest::for_server_render( $page ) );
	if ( $result->is_error() ) {
		throw new RuntimeException( 'api key embed failed: ' . $result->error_code() );
	}
	if ( false === strpos( $token_url_seen, ContractPaths::token_path() ) ) {
		throw new RuntimeException( 'fallback used the wrong token path: ' . $token_url_seen );
	}
	if ( false !== strpos( $token_url_seen, ContractPaths::connect_token_path() ) && ContractPaths::token_path() !== ContractPaths::connect_token_path() ) {
		if ( substr( $token_url_seen, -strlen( ContractPaths::connect_token_path() ) ) === ContractPaths::connect_token_path() ) {
			throw new RuntimeException( 'fallback posted to the Connect token path' );
		}
	}

	StudioClient::set_test_http_handlers( null, null );
}

function test_disconnect_clears_connect_tokens_then_uses_api_keys(): void {
	rs_seed_settings();
	StudioClient::from_wp_options()->flush_token_cache();
	$tokens = new ConnectTokens( 'rsoauth_at_old', 'rsoauth_rt_old', time() + 3600 );
	$tokens->save();

	if ( 'disconnected' !== ConnectFlow::disconnect() ) {
		throw new RuntimeException( 'expected disconnected notice' );
	}
	if ( null !== ConnectTokens::load() ) {
		throw new RuntimeException( 'disconnect left Connect tokens in place' );
	}

	$grant = '';
	$bearer = '';
	StudioClient::set_test_http_handlers(
		function ( string $url, array $fields ) use ( &$grant ) {
			$grant = (string) ( $fields['grant_type'] ?? '' );
			if ( false === strpos( $url, ContractPaths::token_path() ) ) {
				throw new RuntimeException( 'post-disconnect token URL was not the named API path' );
			}
			return array(
				'status' => 200,
				'body'   => array(
					'access_token' => 'api-key-after-disconnect',
					'expires_in'   => 3600,
				),
			);
		},
		function ( string $url, array $headers ) use ( &$bearer ) {
			$bearer = $headers['Authorization'] ?? '';
			return array(
				'status' => 200,
				'body'   => rs_sample_browser_payload(),
			);
		}
	);

	$page = PageRecordingId::parse( RS_TEST_PAGE_UUID );
	$result = StudioClient::from_wp_options()->embed_payload_for_page( $page, EmbedRequest::for_server_render( $page ) );
	if ( $result->is_error() ) {
		throw new RuntimeException( 'embed after disconnect failed: ' . $result->error_code() );
	}
	if ( ContractPaths::TOKEN_GRANT !== $grant ) {
		throw new RuntimeException( 'embed after disconnect should use client_credentials' );
	}
	if ( 'Bearer api-key-after-disconnect' !== $bearer ) {
		throw new RuntimeException( 'embed after disconnect used the wrong bearer' );
	}

	StudioClient::set_test_http_handlers( null, null );
}

function test_settings_markup_never_prints_connect_tokens(): void {
	$stored = array(
		'host_base_url'      => 'http://localhost:3000',
		'client_id'          => 'wp-public-client',
		'client_secret'      => 'super-secret',
		'token_url_override' => '',
		'access_token'       => 'rsoauth_at_should_never_render',
		'refresh_token'      => 'rsoauth_rt_should_never_render',
	);

	$html = SettingsPage::markup(
		$stored,
		'connected',
		true,
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start',
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect'
	);

	if ( false === strpos( $html, 'Connect to Recording Studio' ) && false === strpos( $html, 'Disconnect' ) ) {
		throw new RuntimeException( 'settings markup missing Connect or Disconnect' );
	}
	if ( false === strpos( $html, 'Disconnect' ) ) {
		throw new RuntimeException( 'connected settings should show Disconnect' );
	}
	if ( false !== strpos( $html, 'rsoauth_at_should_never_render' ) || false !== strpos( $html, 'rsoauth_rt_should_never_render' ) ) {
		throw new RuntimeException( 'Connect tokens leaked into settings HTML' );
	}
	if ( false === strpos( $html, 'WordPress Plugin Demo' ) ) {
		throw new RuntimeException( 'settings title missing product name' );
	}
	if ( preg_match( '/\b(recordable|actor|root)\b/i', $html ) ) {
		throw new RuntimeException( 'settings HTML used backend words' );
	}
}

function test_plugin_settings_complete_with_api_keys_without_connect_tokens(): void {
	$settings = PluginSettings::validate_and_merge(
		array(
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'client',
			'client_secret' => 'secret',
		)
	);
	if ( ! $settings->is_complete() ) {
		throw new RuntimeException( 'host+id+secret should still be complete' );
	}
}
