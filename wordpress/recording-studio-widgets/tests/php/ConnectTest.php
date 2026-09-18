<?php

declare(strict_types=1);

use RecordingStudio\ConnectFlow;
use RecordingStudio\ConnectNotice;
use RecordingStudio\ConnectSession;
use RecordingStudio\ConnectStatus;
use RecordingStudio\ConnectTokens;
use RecordingStudio\ContractPaths;
use RecordingStudio\EmbedRequest;
use RecordingStudio\HostUrls;
use RecordingStudio\PageRecordingId;
use RecordingStudio\Placeholder;
use RecordingStudio\Pkce;
use RecordingStudio\PluginSettings;
use RecordingStudio\SettingsPage;
use RecordingStudio\StudioClient;

require_once dirname( __DIR__, 2 ) . '/includes/settings-page.php';

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

function rs_post_connect_start( array $posted ): string {
	$GLOBALS['rs_test_filters'] = array();
	$_POST                      = $posted;
	ob_start();
	try {
		recording_studio_plugin_demo_connect_start();
		ob_end_clean();
		throw new RuntimeException( 'connect start did not halt after the leaving page' );
	} catch ( RsTestHaltException $halt ) {
		return (string) ob_get_clean();
	} catch ( RsTestRedirectException $redirect ) {
		ob_end_clean();
		throw new RuntimeException( 'connect start redirected instead of showing the leaving page: ' . $redirect->getMessage() );
	}
}

function rs_assert_handoff_to_host( string $html, string $host, string $scheme = 'https' ): void {
	if ( false === strpos( $html, 'Taking you to Recording Studio to connect…' ) ) {
		throw new RuntimeException( 'leaving page missing heading: ' . $html );
	}
	if ( false === strpos( $html, 'Continue to Recording Studio' ) ) {
		throw new RuntimeException( 'leaving page missing noscript continue link' );
	}
	if ( false === strpos( $html, 'http-equiv="refresh"' ) || false === strpos( $html, 'content="1;url=' ) ) {
		throw new RuntimeException( 'leaving page missing delayed meta refresh' );
	}
	if ( false === strpos( $html, 'location.replace(' ) ) {
		throw new RuntimeException( 'leaving page missing script redirect' );
	}

	if ( ! preg_match( '/<a href="([^"]+)"/', $html, $match ) ) {
		throw new RuntimeException( 'leaving page missing continue href' );
	}

	$location = html_entity_decode( $match[1], ENT_QUOTES, 'UTF-8' );
	$parts    = parse_url( $location );
	if ( $scheme !== ( $parts['scheme'] ?? '' ) || $host !== ( $parts['host'] ?? '' ) ) {
		throw new RuntimeException( 'leaving page did not point at the configured host: ' . $location );
	}
	if ( '/recording_studio_oauth/oauth/authorize' !== ( $parts['path'] ?? '' ) ) {
		throw new RuntimeException( 'leaving page path was not authorize: ' . $location );
	}
	if ( 'http://localhost:8888/wp-admin/' === $location ) {
		throw new RuntimeException( 'connect start fell back to admin' );
	}
}

function test_connect_start_shows_leaving_page_for_configured_host(): void {
	$html = rs_post_connect_start(
		array(
			'rs_host_base_url'      => 'https://abc.trycloudflare.com',
			'rs_client_id'          => 'wp-public-client',
			'rs_client_secret'      => '',
			'rs_token_url_override' => '',
		)
	);
	rs_assert_handoff_to_host( $html, 'abc.trycloudflare.com' );

	try {
		wp_safe_redirect( 'https://evil.example/phish' );
		throw new RuntimeException( 'unrelated host redirect did not run' );
	} catch ( RsTestRedirectException $denied ) {
		if ( 'http://localhost:8888/wp-admin/' !== $denied->getMessage() ) {
			throw new RuntimeException( 'unrelated host was allowed: ' . $denied->getMessage() );
		}
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
			if ( false === strpos( $token_url, '/recording_studio_api/apis/wp_plugin_demo/oauth/token' ) ) {
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
	if ( false === strpos( $token_url_seen, '/recording_studio_api/apis/wp_plugin_demo/oauth/token' ) ) {
		throw new RuntimeException( 'fallback used the wrong token path: ' . $token_url_seen );
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

	$connect_url    = 'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start';
	$disconnect_url = 'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect';
	$html           = SettingsPage::markup(
		$stored,
		'connected',
		new ConnectStatus( true ),
		$connect_url,
		$disconnect_url
	);

	if ( false === strpos( $html, 'This site is connected.' ) ) {
		throw new RuntimeException( 'connected settings missing status' );
	}
	if ( false === strpos( $html, 'Disconnect' ) ) {
		throw new RuntimeException( 'connected settings should show Disconnect' );
	}
	if ( false === strpos( $html, 'Connect again' ) ) {
		throw new RuntimeException( 'connected settings should show Connect again' );
	}
	if ( false === strpos( $html, $connect_url ) ) {
		throw new RuntimeException( 'Connect again must use the same start action as Connect' );
	}
	if ( false !== strpos( $html, 'Connect to Recording Studio' ) ) {
		throw new RuntimeException( 'connected settings should not show the primary Connect button' );
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

function rs_settings_markup( bool $connected, string $notice = '' ): string {
	return SettingsPage::markup(
		array(
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'wp-public-client',
			'client_secret' => 'super-secret',
		),
		$notice,
		new ConnectStatus( $connected ),
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start',
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect'
	);
}

function rs_finish_with_token_error_body( string $host_error ): string {
	rs_seed_connect_settings_without_secret();
	$settings = PluginSettings::load();
	$url      = ConnectFlow::start( $settings, new HostUrls( $settings ) );
	$parts    = parse_url( $url );
	$query    = array();
	parse_str( (string) ( $parts['query'] ?? '' ), $query );

	StudioClient::set_test_http_handlers(
		static function () use ( $host_error ) {
			return array(
				'status' => 400,
				'body'   => array( 'error' => $host_error ),
			);
		},
		null
	);

	$notice = ConnectFlow::finish(
		array(
			'code'  => 'auth-code-1',
			'state' => $query['state'],
		),
		StudioClient::from_wp_options()
	);
	StudioClient::set_test_http_handlers( null, null );
	return $notice;
}

function test_settings_markup_when_disconnected_shows_primary_connect(): void {
	$html = rs_settings_markup( false );
	if ( false === strpos( $html, 'Connect to Recording Studio' ) ) {
		throw new RuntimeException( 'disconnected settings missing primary Connect' );
	}
	if ( false !== strpos( $html, 'Connect again' ) ) {
		throw new RuntimeException( 'disconnected settings should not show Connect again' );
	}
	if ( false !== strpos( $html, 'This site is connected.' ) ) {
		throw new RuntimeException( 'disconnected settings should not claim the site is connected' );
	}
}

function test_connect_again_shows_leaving_page_while_already_connected(): void {
	rs_seed_connect_settings_without_secret();
	$tokens = new ConnectTokens( 'rsoauth_at_keep', 'rsoauth_rt_keep', time() + 3600 );
	$tokens->save();
	if ( ! ConnectStatus::current()->connected ) {
		throw new RuntimeException( 'expected connected status from stored tokens' );
	}

	$html = rs_post_connect_start(
		array(
			'rs_host_base_url'      => 'http://localhost:3000',
			'rs_client_id'          => 'wp-public-client',
			'rs_client_secret'      => '',
			'rs_token_url_override' => '',
		)
	);
	rs_assert_handoff_to_host( $html, 'localhost', 'http' );

	$stored = ConnectTokens::load();
	if ( null === $stored || 'rsoauth_at_keep' !== $stored->access_token ) {
		throw new RuntimeException( 'Connect again cleared tokens before PKCE finished' );
	}
}

function test_connected_status_banner_is_visible_without_return_notice(): void {
	$html = rs_settings_markup( true, '' );
	if ( 1 !== substr_count( $html, 'This site is connected.' ) ) {
		throw new RuntimeException( 'connected settings should show the status banner once without a return notice' );
	}
	if ( false === strpos( $html, 'notice notice-success' ) ) {
		throw new RuntimeException( 'connected status should use a success banner' );
	}
	if ( false !== strpos( $html, 'is-dismissible' ) ) {
		throw new RuntimeException( 'status banner should remain after refresh, so it is not dismissible' );
	}
}

function test_connected_return_notice_keeps_connected_code_and_status_banner(): void {
	$html = rs_settings_markup( true, 'connected' );
	if ( 2 !== substr_count( $html, 'This site is connected.' ) ) {
		throw new RuntimeException( 'return notice plus status banner should both say the site is connected' );
	}
	if ( false === strpos( $html, 'notice notice-success is-dismissible' ) ) {
		throw new RuntimeException( 'ConnectNotice::CONNECTED should still render the return notice' );
	}
}

function test_connect_again_does_not_clear_existing_tokens(): void {
	rs_seed_connect_settings_without_secret();
	$tokens = new ConnectTokens( 'rsoauth_at_keep', 'rsoauth_rt_keep', time() + 3600 );
	$tokens->save();
	if ( ! ConnectStatus::current()->connected ) {
		throw new RuntimeException( 'expected connected status from stored tokens' );
	}

	$settings = PluginSettings::load();
	ConnectFlow::start( $settings, new HostUrls( $settings ) );
	$stored = ConnectTokens::load();
	if ( null === $stored || 'rsoauth_at_keep' !== $stored->access_token ) {
		throw new RuntimeException( 'Connect again cleared tokens before PKCE finished' );
	}
}

function test_failed_reconnect_keeps_existing_tokens(): void {
	rs_seed_connect_settings_without_secret();
	$tokens = new ConnectTokens( 'rsoauth_at_keep', 'rsoauth_rt_keep', time() + 3600 );
	$tokens->save();

	$settings = PluginSettings::load();
	$url      = ConnectFlow::start( $settings, new HostUrls( $settings ) );
	$parts    = parse_url( $url );
	$query    = array();
	parse_str( (string) ( $parts['query'] ?? '' ), $query );

	StudioClient::set_test_http_handlers(
		static function () {
			return array(
				'status' => 400,
				'body'   => array( 'error' => 'invalid_grant' ),
			);
		},
		null
	);

	$notice = ConnectFlow::finish(
		array(
			'code'  => 'auth-code-1',
			'state' => $query['state'],
		),
		StudioClient::from_wp_options()
	);
	StudioClient::set_test_http_handlers( null, null );

	if ( ConnectNotice::EXPIRED_OR_USED_CODE !== $notice ) {
		throw new RuntimeException( 'expected expired_or_used_code, got ' . $notice );
	}

	$stored = ConnectTokens::load();
	if ( null === $stored || 'rsoauth_at_keep' !== $stored->access_token ) {
		throw new RuntimeException( 'failed reconnect cleared the previous tokens' );
	}
}

function test_token_error_body_maps_invalid_client_notice(): void {
	$notice = rs_finish_with_token_error_body( 'invalid_client' );
	if ( ConnectNotice::INVALID_CLIENT !== $notice ) {
		throw new RuntimeException( 'expected invalid_client, got ' . $notice );
	}

	$html = rs_settings_markup( false, $notice );
	if ( false === strpos( $html, 'That client id is not for this host. Paste the public WordPress Plugin Demo id.' ) ) {
		throw new RuntimeException( 'invalid_client notice copy missing' );
	}
}

function test_token_error_body_maps_redirect_mismatch_notice(): void {
	$notice = rs_finish_with_token_error_body( 'redirect_uri_mismatch' );
	if ( ConnectNotice::REDIRECT_URI_MISMATCH !== $notice ) {
		throw new RuntimeException( 'expected redirect_uri_mismatch, got ' . $notice );
	}

	$html = rs_settings_markup( false, $notice );
	if ( false === strpos( $html, 'This WordPress address is not registered yet. Add the exact callback URL in Registered apps.' ) ) {
		throw new RuntimeException( 'redirect mismatch notice copy missing' );
	}
}

function test_token_error_body_maps_expired_code_notice(): void {
	$notice = rs_finish_with_token_error_body( 'invalid_grant' );
	if ( ConnectNotice::EXPIRED_OR_USED_CODE !== $notice ) {
		throw new RuntimeException( 'expected expired_or_used_code, got ' . $notice );
	}

	$html = rs_settings_markup( false, $notice );
	if ( false === strpos( $html, 'That connection expired. Connect again.' ) ) {
		throw new RuntimeException( 'expired notice copy missing' );
	}
}

function test_invalid_redirect_uri_query_maps_to_redirect_notice(): void {
	rs_seed_connect_settings_without_secret();
	$notice = ConnectFlow::finish(
		array( 'error' => 'invalid_redirect_uri' ),
		StudioClient::from_wp_options()
	);
	if ( ConnectNotice::REDIRECT_URI_MISMATCH !== $notice ) {
		throw new RuntimeException( 'expected redirect_uri_mismatch, got ' . $notice );
	}
}

function test_saved_connect_tokens_never_appear_in_settings_html(): void {
	rs_seed_connect_settings_without_secret();
	$tokens = new ConnectTokens( 'rsoauth_at_hidden', 'rsoauth_rt_hidden', time() + 3600 );
	$tokens->save();

	$html = SettingsPage::markup(
		array(
			'host_base_url' => 'http://localhost:3000',
			'client_id'     => 'wp-public-client',
			'client_secret' => 'super-secret',
		),
		'',
		ConnectStatus::current(),
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start',
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect'
	);

	if ( false !== strpos( $html, 'rsoauth_at_hidden' ) || false !== strpos( $html, 'rsoauth_rt_hidden' ) ) {
		throw new RuntimeException( 'stored Connect tokens leaked into settings HTML' );
	}
	if ( false === strpos( $html, 'Connect again' ) ) {
		throw new RuntimeException( 'connected status from stored tokens should show Connect again' );
	}
}

function test_expired_connect_refresh_keeps_tokens_and_asks_reconnect(): void {
	rs_seed_connect_settings_without_secret();
	$tokens = new ConnectTokens( 'rsoauth_at_stale', 'rsoauth_rt_stale', time() - 120 );
	$tokens->save();

	StudioClient::set_test_http_handlers(
		static function () {
			return array(
				'status' => 400,
				'body'   => array( 'error' => 'invalid_grant' ),
			);
		},
		static function () {
			throw new RuntimeException( 'embed should not run after a failed refresh' );
		}
	);

	$page = PageRecordingId::parse( RS_TEST_PAGE_UUID );
	if ( $page instanceof \RecordingStudio\EmbedResult ) {
		throw new RuntimeException( 'invalid test uuid' );
	}

	$result = StudioClient::from_wp_options()->embed_payload_for_page( $page, EmbedRequest::for_editor( $page ) );
	if ( ! $result->is_error() || ConnectNotice::RECONNECT_NEEDED !== $result->error_code() ) {
		throw new RuntimeException( 'expected reconnect_needed, got ' . ( $result->is_error() ? $result->error_code() : 'ok' ) );
	}

	$stored = ConnectTokens::load();
	if ( null === $stored || 'rsoauth_at_stale' !== $stored->access_token ) {
		throw new RuntimeException( 'failed refresh cleared tokens' );
	}

	StudioClient::set_test_http_handlers( null, null );
}

function test_editor_embed_copy_shares_connect_notice_codes(): void {
	$page = PageRecordingId::parse( RS_TEST_PAGE_UUID );
	if ( $page instanceof \RecordingStudio\EmbedResult ) {
		throw new RuntimeException( 'invalid test uuid' );
	}

	$editor = EmbedRequest::for_editor( $page );
	$not_found = Placeholder::embed_error_message( ConnectNotice::EMBED_NOT_FOUND, $editor );
	if ( 'No page with that id. Pick a page or paste a fresh id.' !== $not_found ) {
		throw new RuntimeException( 'editor embed_not_found copy mismatch: ' . $not_found );
	}

	$unauthorized = Placeholder::embed_error_message( ConnectNotice::EMBED_UNAUTHORIZED, $editor );
	if ( 'Host said no. Connect again or check Advanced API keys.' !== $unauthorized ) {
		throw new RuntimeException( 'editor embed_unauthorized copy mismatch: ' . $unauthorized );
	}

	$front = Placeholder::front_message();
	if ( preg_match( '/\b(recording|recordable|actor|root)\b/i', $front ) ) {
		throw new RuntimeException( 'front placeholder used backend words' );
	}
}
