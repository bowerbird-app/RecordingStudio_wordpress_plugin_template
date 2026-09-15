<?php
/**
 * Named API path segments for wp_plugin_demo (mirror dummy WpPluginDemo::Contract).
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

namespace RecordingStudio;

final class ContractPaths {
	public const API_KEY      = 'wp_plugin_demo';
	public const ENGINE_MOUNT = '/recording_studio_api';
	public const NAMED_PREFIX = self::ENGINE_MOUNT . '/apis/' . self::API_KEY;
	public const API_VERSION  = 'v1';
	public const TOKEN_GRANT  = 'client_credentials';

	public static function token_path(): string {
		return self::NAMED_PREFIX . '/oauth/token';
	}

	public static function embed_path( string $page_recording_uuid ): string {
		return self::NAMED_PREFIX . '/' . self::API_VERSION . '/pages/' . $page_recording_uuid . '/actions/embed';
	}
}
