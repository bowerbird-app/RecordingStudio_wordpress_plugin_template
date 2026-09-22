<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ContractPaths {
	public const API_KEY                     = 'wp_plugin_demo';
	public const ENGINE_MOUNT                = '/recording_studio_api';
	public const NAMED_PREFIX                = self::ENGINE_MOUNT . '/apis/' . self::API_KEY;
	public const API_VERSION                 = 'v1';
	public const TOKEN_GRANT                 = 'client_credentials';
	public const CONNECT_GRANT               = 'authorization_code';
	public const REFRESH_GRANT               = 'refresh_token';
	public const AUTHORIZE_PATH              = '/recording_studio_oauth/oauth/authorize';
	public const CENTRAL_RELAY_CONNECT_PATH  = '/recording_studio_oauth/connect';
	public const CENTRAL_RELAY_CALLBACK_PATH = '/recording_studio_oauth/callback';

	public static function token_path(): string {
		return self::NAMED_PREFIX . '/oauth/token';
	}

	public static function authorize_path(): string {
		return self::AUTHORIZE_PATH;
	}

	public static function central_relay_connect_path(): string {
		return self::CENTRAL_RELAY_CONNECT_PATH;
	}

	public static function central_relay_callback_path(): string {
		return self::CENTRAL_RELAY_CALLBACK_PATH;
	}

	public static function connect_token_path(): string {
		return self::token_path();
	}

	public static function pages_path(): string {
		return self::NAMED_PREFIX . '/' . self::API_VERSION . '/pages';
	}

	public static function embed_path( string $page_recording_uuid ): string {
		return self::pages_path() . '/' . $page_recording_uuid . '/actions/embed';
	}
}
