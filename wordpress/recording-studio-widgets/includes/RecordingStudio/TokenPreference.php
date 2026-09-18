<?php

declare(strict_types=1);

namespace RecordingStudio;

final class TokenPreference {
	public const CONNECT    = 'connect';
	public const API_KEYS   = 'api_keys';
	public const INCOMPLETE = 'incomplete';

	/** @var string */
	public string $source;

	/** @var ConnectTokens|null */
	public ?ConnectTokens $connect_tokens;

	private function __construct( string $source, ?ConnectTokens $connect_tokens ) {
		$this->source         = $source;
		$this->connect_tokens = $connect_tokens;
	}

	public static function resolve( PluginSettings $settings, ?ConnectTokens $tokens ): self {
		if ( null !== $tokens && $tokens->usable() ) {
			return new self( self::CONNECT, $tokens );
		}

		if ( $settings->has_api_keys() ) {
			return new self( self::API_KEYS, null );
		}

		return new self( self::INCOMPLETE, null );
	}

	public function is_complete(): bool {
		return self::INCOMPLETE !== $this->source;
	}

	public function uses_connect(): bool {
		return self::CONNECT === $this->source;
	}

	public function uses_api_keys(): bool {
		return self::API_KEYS === $this->source;
	}
}
