<?php

declare(strict_types=1);

use RecordingStudio\Assets;
use RecordingStudio\BlockAttributes;
use RecordingStudio\BlockShell;
use RecordingStudio\EmbedRequest;
use RecordingStudio\EmbedResult;
use RecordingStudio\PageRecordingId;
use RecordingStudio\Placeholder;
use RecordingStudio\StudioClient;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$attrs = BlockAttributes::from_block_props( $attributes );
if ( ! $attrs->has_page_recording_id() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo Placeholder::front_message();
	return;
}

$parsed = PageRecordingId::parse( $attrs->page_recording_id );
if ( $parsed instanceof EmbedResult ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo Placeholder::embed_error_markup( $parsed->error_code() );
	return;
}

$client = StudioClient::from_wp_options();
$result = $client->embed_payload_for_page( $parsed, EmbedRequest::for_server_render( $parsed ) );

if ( $result->is_error() ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo Placeholder::embed_error_markup( $result->error_code(), EmbedRequest::for_server_render( $parsed ) );
	return;
}

Assets::enqueue_sdk();
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo BlockShell::render( $attrs, $result->payload() );
