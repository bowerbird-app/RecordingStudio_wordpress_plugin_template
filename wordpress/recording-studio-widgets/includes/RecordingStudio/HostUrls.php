<?php

declare(strict_types=1);

namespace RecordingStudio;

final class HostUrls {
	/** @var PluginSettings */
	private $settings;

	public function __construct( PluginSettings $settings ) {
		$this->settings = $settings;
	}

	public function token_post_url(): string {
		if ( null !== $this->settings->token_url_override && '' !== $this->settings->token_url_override ) {
			return $this->settings->token_url_override;
		}

		return $this->settings->host_base_url . ContractPaths::token_path();
	}

	public function embed_get_url( PageRecordingId $page_recording_id ): string {
		return $this->settings->host_base_url . ContractPaths::embed_path( $page_recording_id->to_string() );
	}
}
