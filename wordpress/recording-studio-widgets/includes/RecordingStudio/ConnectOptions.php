<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ConnectOptions {
	/**
	 * @var (callable(string): array{status: int, body: mixed})|null
	 */
	private static $test_get = null;

	/**
	 * @param (callable(string): array{status: int, body: mixed})|null $get
	 */
	public static function set_test_get( ?callable $get ): void {
		self::$test_get = $get;
	}

	public static function request_url( PluginSettings $settings ): string {
		return $settings->host_base_url
			. ContractPaths::connect_options_path()
			. '?'
			. http_build_query(
				array(
					'client_id' => $settings->client_id,
				)
			);
	}

	public static function for_settings( PluginSettings $settings ): RegistrationOffer {
		if ( '' === $settings->host_base_url || '' === $settings->client_id ) {
			return RegistrationOffer::hidden();
		}

		$response = self::get( self::request_url( $settings ) );
		if ( 200 !== (int) ( $response['status'] ?? 0 ) ) {
			return RegistrationOffer::hidden();
		}

		return RegistrationOffer::from_body( $response['body'] ?? null );
	}

	private static function get( string $url ): array {
		if ( null !== self::$test_get ) {
			$result = ( self::$test_get )( $url );
			if ( ! is_array( $result ) ) {
				return array(
					'status' => 0,
					'body'   => null,
				);
			}

			return $result;
		}

		if ( ! function_exists( 'wp_remote_get' ) ) {
			return array(
				'status' => 0,
				'body'   => null,
			);
		}

		$response = wp_remote_get(
			$url,
			array(
				'timeout'     => 8,
				'redirection' => 0,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'status' => 0,
				'body'   => null,
			);
		}

		$raw  = wp_remote_retrieve_body( $response );
		$body = json_decode( $raw, true );

		return array(
			'status' => (int) wp_remote_retrieve_response_code( $response ),
			'body'   => $body,
		);
	}
}
