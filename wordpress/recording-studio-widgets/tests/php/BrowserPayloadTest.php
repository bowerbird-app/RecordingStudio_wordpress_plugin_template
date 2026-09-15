<?php

declare(strict_types=1);

use RecordingStudio\BrowserPayload;
use RecordingStudio\EmbedResult;

function test_browser_payload_accepts_v1(): void {
	$parsed = BrowserPayload::from_json( rs_sample_browser_payload() );
	if ( $parsed instanceof EmbedResult ) {
		throw new RuntimeException( 'expected BrowserPayload, got error' );
	}

	$roundtrip = $parsed->to_array();
	if ( $roundtrip['html'] !== rs_sample_browser_payload()['html'] ) {
		throw new RuntimeException( 'to_array did not preserve html' );
	}
}

function test_browser_payload_rejects_v2(): void {
	$body                     = rs_sample_browser_payload();
	$body['schema_version'] = 2;
	$parsed                   = BrowserPayload::from_json( $body );
	if ( ! ( $parsed instanceof EmbedResult ) || ! $parsed->is_error() ) {
		throw new RuntimeException( 'expected schema_version error' );
	}
}
