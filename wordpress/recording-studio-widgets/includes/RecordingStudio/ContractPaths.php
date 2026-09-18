<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ContractPaths {
	public const API_KEY             = 'wp_plugin_demo';
	public const ENGINE_MOUNT        = '/recording_studio_api';
	public const NAMED_PREFIX        = self::ENGINE_MOUNT . '/apis/' . self::API_KEY;
	public const API_VERSION         = 'v1';
	public const TOKEN_GRANT         = 'client_credentials';
	public const CONNECT_GRANT       = 'authorization_code';
	public const REFRESH_GRANT       = 'refresh_token';
	public const AUTHORIZE_PATH      = '/recording_studio_oauth/oauth/authorize';
	public const CONNECT_TOKEN_PATH  = self::ENGINE_MOUNT . '/oauth/token';

	public static function token_path(): string {
		return self::NAMED_PREFIX . '/oauth/token';
	}

	public static function authorize_path(): string {
		return self::AUTHORIZE_PATH;
	}

	public static function connect_token_path(): string {
		return self::CONNECT_TOKEN_PATH;
	}

	public static function embed_path( string $page_recording_uuid ): string {
		return self::NAMED_PREFIX . '/' . self::API_VERSION . '/pages/' . $page_recording_uuid . '/actions/embed';
	}
}
