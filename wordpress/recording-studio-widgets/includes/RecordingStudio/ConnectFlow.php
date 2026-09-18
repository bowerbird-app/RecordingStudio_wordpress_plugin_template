<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ConnectFlow {
	public const START_ACTION      = 'recording_studio_oauth_start';
	public const CALLBACK_ACTION   = 'recording_studio_oauth_callback';
	public const DISCONNECT_ACTION = 'recording_studio_oauth_disconnect';

	public static function callback_uri(): string {
		return admin_url( 'admin-post.php?action=' . self::CALLBACK_ACTION );
	}

	public static function start( PluginSettings $settings, HostUrls $urls ): string {
		$pkce         = Pkce::generate();
		$state        = wp_generate_password( 32, false, false );
		$redirect_uri = self::callback_uri();
		$session      = new ConnectSession( $pkce->verifier, $state, $redirect_uri );
		$session->persist();

		return $urls->authorize_url(
			array(
				'response_type'         => 'code',
				'client_id'             => $settings->client_id,
				'redirect_uri'          => $redirect_uri,
				'state'                 => $state,
				'code_challenge'        => $pkce->challenge,
				'code_challenge_method' => 'S256',
			)
		);
	}

	/**
	 * @param array<string, mixed> $query
	 */
	public static function finish( array $query, StudioClient $client ): string {
		$query_error = HostError::parse( $query );
		if ( null !== $query_error ) {
			ConnectSession::clear();
			return $query_error->notice_code();
		}

		$session = ConnectSession::load();
		if ( null === $session ) {
			return ConnectNotice::CONNECT_FAILED;
		}

		$state = isset( $query['state'] ) ? (string) $query['state'] : '';
		if ( '' === $state || ! hash_equals( $session->state, $state ) ) {
			ConnectSession::clear();
			return ConnectNotice::CONNECT_FAILED;
		}

		$code = isset( $query['code'] ) ? (string) $query['code'] : '';
		if ( '' === $code ) {
			ConnectSession::clear();
			return ConnectNotice::CONNECT_FAILED;
		}

		$tokens = $client->exchange_connect_code( $code, $session->redirect_uri, $session->verifier );
		ConnectSession::clear();
		if ( $tokens instanceof HostError ) {
			return $tokens->notice_code();
		}
		if ( $tokens instanceof EmbedResult ) {
			return ConnectNotice::CONNECT_FAILED;
		}

		$tokens->save();
		return ConnectNotice::CONNECTED;
	}

	public static function disconnect(): string {
		ConnectTokens::clear();
		return ConnectNotice::DISCONNECTED;
	}
}
