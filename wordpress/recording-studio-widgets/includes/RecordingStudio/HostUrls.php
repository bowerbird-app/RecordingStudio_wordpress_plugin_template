<?php

declare(strict_types=1);

namespace RecordingStudio;

final class HostUrls {
	/** @var PluginSettings */
	private PluginSettings $settings;

	public function __construct( PluginSettings $settings ) {
		$this->settings = $settings;
	}

	public function token_post_url(): string {
		return $this->settings->host_base_url . ContractPaths::token_path();
	}

	public function connect_token_post_url(): string {
		return $this->settings->host_base_url . ContractPaths::connect_token_path();
	}

	public function relay_callback_url(): string {
		return $this->settings->host_base_url . ContractPaths::wordpress_callback_path();
	}

	/**
	 * @param array<string, string> $query
	 */
	public function wordpress_connect_url( array $query ): string {
		return $this->settings->host_base_url . ContractPaths::wordpress_connect_path() . '?' . http_build_query( $query );
	}

	public function pages_get_url(): string {
		return $this->settings->host_base_url . ContractPaths::pages_path();
	}

	public function embed_get_url( PageRecordingId $page_recording_id ): string {
		return $this->settings->host_base_url . ContractPaths::embed_path( $page_recording_id->to_string() );
	}
}
