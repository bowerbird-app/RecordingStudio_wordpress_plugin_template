<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ConnectStatus {
	/** @var bool */
	public bool $connected;

	public function __construct( bool $connected ) {
		$this->connected = $connected;
	}

	public static function current(): self {
		return new self( ConnectTokens::connected() );
	}
}
