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

	public function connect_token_post_url(): string {
		return $this->settings->host_base_url . ContractPaths::connect_token_path();
	}

	/**
	 * @param array<string, string> $query Authorize query.
	 */
	public function authorize_url( array $query ): string {
		return $this->settings->host_base_url . ContractPaths::authorize_path() . '?' . http_build_query( $query );
	}

	public function embed_get_url( PageRecordingId $page_recording_id ): string {
		return $this->settings->host_base_url . ContractPaths::embed_path( $page_recording_id->to_string() );
	}
}
