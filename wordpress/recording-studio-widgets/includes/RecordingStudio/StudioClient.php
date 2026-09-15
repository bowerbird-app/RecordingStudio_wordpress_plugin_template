<?php

declare(strict_types=1);

namespace RecordingStudio;

final class StudioClient {
	private const TOKEN_CACHE_PREFIX = 'recording_studio_plugin_demo_token_cache_';

	/**
	 * @var null|callable(string, array<string, mixed>): array{status: int, body: mixed, error?: string}
	 */
	private static $test_http_post = null;

	/**
	 * @var null|callable(string, array<string, mixed>): array{status: int, body: mixed, error?: string}
	 */
	private static $test_http_get = null;

	/** @var PluginSettings */
	private $settings;

	/** @var HostUrls */
	private $urls;

	private function __construct( PluginSettings $settings, HostUrls $urls ) {
		$this->settings = $settings;
		$this->urls     = $urls;
	}

	public static function from_wp_options(): self {
		$settings = PluginSettings::load();
		if ( null === $settings ) {
			$settings = new PluginSettings( '', '', '' );
		}

		return new self( $settings, new HostUrls( $settings ) );
	}

	/**
	 * @param null|callable(string, array<string, mixed>): array{status: int, body: mixed, error?: string} $post
	 * @param null|callable(string, array<string, mixed>): array{status: int, body: mixed, error?: string} $get
	 */
	public static function set_test_http_handlers( ?callable $post, ?callable $get ): void {
		self::$test_http_post = $post;
		self::$test_http_get  = $get;
	}

	public function embed_payload_for_page( PageRecordingId $page_recording_id, ?EmbedRequest $request = null ): EmbedResult {
		$request = $request ?? EmbedRequest::for_server_render( $page_recording_id );

		if ( ! $this->settings->is_complete() ) {
			return EmbedResult::err(
				'settings_incomplete',
				Placeholder::settings_incomplete_message(),
			);
		}

		$token_result = $this->ensure_access_token();
		if ( $token_result instanceof EmbedResult ) {
			return $token_result;
		}

		return $this->request_embed( $request, (string) $token_result );
	}

	public function probe_credentials(): EmbedResult {
		if ( ! $this->settings->is_complete() ) {
			return EmbedResult::err(
				'settings_incomplete',
				Placeholder::settings_incomplete_message(),
			);
		}

		$this->flush_token_cache();
		$token_result = $this->ensure_access_token();
		if ( $token_result instanceof EmbedResult ) {
			return $token_result;
		}

		return EmbedResult::probe_ok();
	}

	public function flush_token_cache(): void {
		CachedAccessToken::clear( $this->token_cache_key() );
	}

	private function token_cache_key(): string {
		return self::TOKEN_CACHE_PREFIX . $this->settings->fingerprint();
	}

	/**
	 * @return EmbedResult|string
	 */
	private function ensure_access_token() {
		$cached = CachedAccessToken::load( $this->token_cache_key() );
		if ( null !== $cached && $cached->is_valid( time() ) ) {
			return $cached->access_token;
		}

		return $this->request_token();
	}

	/**
	 * @return EmbedResult|string
	 */
	private function request_token() {
		$response = $this->http_post(
			$this->urls->token_post_url(),
			array(
				'grant_type'    => ContractPaths::TOKEN_GRANT,
				'client_id'     => $this->settings->client_id,
				'client_secret' => $this->settings->client_secret,
			)
		);

		if ( ! empty( $response['error'] ) ) {
			return EmbedResult::err(
				'token_http_error',
				__( 'Could not reach the host token endpoint.', 'recording-studio-widget' ),
			);
		}

		$status = (int) $response['status'];
		if ( 200 !== $status ) {
			return EmbedResult::err(
				'token_denied',
				__( 'Host rejected the OAuth client credentials. Check client id and secret.', 'recording-studio-widget' ),
				$status
			);
		}

		$body = $response['body'];
		if ( ! is_array( $body ) || empty( $body['access_token'] ) || ! is_string( $body['access_token'] ) ) {
			return EmbedResult::err(
				'token_invalid',
				__( 'Host returned an unexpected token response.', 'recording-studio-widget' ),
				$status
			);
		}

		$expires_in = isset( $body['expires_in'] ) ? (int) $body['expires_in'] : 3600;
		$expires_in = max( 60, $expires_in );
		$cached     = new CachedAccessToken( $body['access_token'], time() + $expires_in );
		$cached->save( $this->token_cache_key() );

		return $cached->access_token;
	}

	private function request_embed( EmbedRequest $request, string $bearer ): EmbedResult {
		$page_recording_id = $request->page_recording_id();
		$response          = $this->http_get(
			$this->urls->embed_get_url( $page_recording_id ),
			array(
				'Authorization' => 'Bearer ' . $bearer,
				'Accept'        => 'application/json',
			)
		);

		if ( ! empty( $response['error'] ) ) {
			return EmbedResult::err(
				'embed_http_error',
				Placeholder::embed_error_message( 'embed_http_error', $request ),
			);
		}

		$status = (int) $response['status'];
		if ( 401 === $status ) {
			$this->flush_token_cache();
			return EmbedResult::err(
				'embed_unauthorized',
				Placeholder::embed_error_message( 'embed_unauthorized', $request ),
				401
			);
		}

		if ( 404 === $status ) {
			return EmbedResult::err(
				'embed_not_found',
				Placeholder::embed_error_message( 'embed_not_found', $request ),
				404
			);
		}

		if ( 200 !== $status ) {
			return EmbedResult::err(
				'embed_failed',
				Placeholder::embed_error_message( 'embed_failed', $request ),
				$status
			);
		}

		$body = $response['body'];
		if ( ! is_array( $body ) ) {
			return EmbedResult::err(
				'payload_invalid',
				Placeholder::embed_error_message( 'payload_invalid', $request ),
			);
		}

		$parsed = BrowserPayload::from_json( $body );
		if ( $parsed instanceof EmbedResult ) {
			return EmbedResult::err(
				$parsed->error_code(),
				Placeholder::embed_error_message( $parsed->error_code(), $request ),
			);
		}

		return EmbedResult::ok( $parsed );
	}

	/**
	 * @param array<string, string> $fields
	 * @return array{status: int, body: mixed, error?: string}
	 */
	private function http_post( string $url, array $fields ): array {
		if ( null !== self::$test_http_post ) {
			return ( self::$test_http_post )( $url, $fields );
		}

		$response = wp_remote_post(
			$url,
			array(
				'timeout' => 20,
				'body'    => $fields,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 0,
				'body'   => null,
				'error'  => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$body = json_decode( $raw, true );

		return array(
			'status' => $code,
			'body'   => $body,
		);
	}

	/**
	 * @param array<string, string> $headers
	 * @return array{status: int, body: mixed, error?: string}
	 */
	private function http_get( string $url, array $headers ): array {
		if ( null !== self::$test_http_get ) {
			return ( self::$test_http_get )( $url, $headers );
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 20,
				'headers' => $headers,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 0,
				'body'   => null,
				'error'  => $response->get_error_message(),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = wp_remote_retrieve_body( $response );
		$body = json_decode( $raw, true );

		return array(
			'status' => $code,
			'body'   => $body,
		);
	}
}
