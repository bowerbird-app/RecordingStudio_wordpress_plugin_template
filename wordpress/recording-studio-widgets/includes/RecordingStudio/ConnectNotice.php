<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ConnectNotice {
	public const SAVED                     = 'saved';
	public const PROBE_OK                  = 'probe_ok';
	public const PROBE_FAILED              = 'probe_failed';
	public const CONNECTED                 = 'connected';
	public const CONNECT_DENIED            = 'connect_denied';
	public const CONNECT_FAILED            = 'connect_failed';
	public const DISCONNECTED              = 'disconnected';
	public const INVALID_CLIENT            = 'invalid_client';
	public const REDIRECT_URI_MISMATCH     = 'redirect_uri_mismatch';
	public const EXPIRED_OR_USED_CODE      = 'expired_or_used_code';
	public const RECONNECT_NEEDED          = 'reconnect_needed';
	public const EMBED_NOT_FOUND           = 'embed_not_found';
	public const EMBED_UNAUTHORIZED        = 'embed_unauthorized';
	public const INVALID_PAGE_RECORDING_ID = 'invalid_page_recording_id';

	/**
	 * @var array<string, array{0: string, 1: string}>
	 */
	private const ENTRIES = array(
		self::SAVED                     => array( 'success', 'Settings saved.' ),
		self::PROBE_OK                  => array( 'success', 'The host accepted the API keys.' ),
		self::PROBE_FAILED              => array( 'error', 'The host did not accept those API keys. Check the Advanced API key and secret.' ),
		self::CONNECTED                 => array( 'success', 'This site is connected.' ),
		self::CONNECT_DENIED            => array( 'error', 'Connection cancelled. Try again when you are ready.' ),
		self::CONNECT_FAILED            => array( 'error', 'Could not connect. Try again.' ),
		self::DISCONNECTED              => array( 'success', 'Disconnected. Embeds will use Advanced API keys if you saved them.' ),
		self::INVALID_CLIENT            => array( 'error', 'This site could not use the shared app. Try Connect again.' ),
		self::REDIRECT_URI_MISMATCH     => array( 'error', 'This WordPress address was not accepted. Try Connect again.' ),
		self::EXPIRED_OR_USED_CODE      => array( 'error', 'That connection expired. Connect again.' ),
		self::RECONNECT_NEEDED          => array( 'error', 'This site needs to connect again.' ),
		self::EMBED_NOT_FOUND           => array( 'error', 'No page with that id. Pick a page or paste a fresh id.' ),
		self::EMBED_UNAUTHORIZED        => array( 'error', 'Host said no. Connect again or check Advanced API keys.' ),
		self::INVALID_PAGE_RECORDING_ID => array( 'error', 'That is not a valid page id.' ),
	);

	/**
	 * @return array{kind: string, message: string}|null
	 */
	public static function lookup( string $code ): ?array {
		if ( ! isset( self::ENTRIES[ $code ] ) ) {
			return null;
		}

		return array(
			'kind'    => self::ENTRIES[ $code ][0],
			'message' => self::ENTRIES[ $code ][1],
		);
	}

	public static function message( string $code ): string {
		$entry = self::lookup( $code );
		return null === $entry ? '' : $entry['message'];
	}
}
