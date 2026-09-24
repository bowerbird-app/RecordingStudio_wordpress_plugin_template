<?php

declare(strict_types=1);

use RecordingStudio\Placeholder;
use RecordingStudio\ProductConfig;

function rs_embed_empty_body( string $html ): string {
	if ( 1 !== preg_match( '/<p class="rs-embed-empty__body">([^<]*)<\/p>/', $html, $match ) ) {
		throw new RuntimeException( 'empty card missing body: ' . $html );
	}

	return $match[1];
}

function test_front_empty_card_centers_logo_title_and_fixed_body(): void {
	rs_clear_product_config_filter();
	$html = Placeholder::front_message();
	$body = rs_embed_empty_body( $html );
	$logo = ProductConfig::logo_url();

	if ( 'Pick a page on the block settings to embed.' !== $body ) {
		throw new RuntimeException( 'front empty body mismatch: ' . $body );
	}
	if ( false !== strpos( $body, 'WP Template Demo' ) ) {
		throw new RuntimeException( 'front empty body must not include the product name' );
	}
	if ( false === strpos( $html, 'class="rs-embed-empty"' ) ) {
		throw new RuntimeException( 'front empty card missing rs-embed-empty' );
	}
	if ( false === strpos( $html, 'class="rs-embed-empty__title">WP Template Demo</p>' ) ) {
		throw new RuntimeException( 'front empty title should be the product name' );
	}
	if ( '' === $logo || false === strpos( $html, 'class="rs-embed-empty__logo" src="' . esc_url( $logo ) . '"' ) ) {
		throw new RuntimeException( 'front empty card missing the product logo' );
	}
	if ( false !== strpos( $html, 'Pick a page in the block settings' ) ) {
		throw new RuntimeException( 'front empty card still uses the old paragraph' );
	}
}

function test_front_empty_card_omits_a_missing_logo_and_keeps_a_white_label_title(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['name'] = 'Acme Studio';
			$config['logo'] = 'build/brand/missing-logo.jpg';
			return $config;
		}
	);

	$html = Placeholder::front_message();
	$body = rs_embed_empty_body( $html );

	if ( 'Pick a page on the block settings to embed.' !== $body ) {
		throw new RuntimeException( 'white-label body changed: ' . $body );
	}
	if ( false !== strpos( $body, 'Acme Studio' ) ) {
		throw new RuntimeException( 'white-label body must stay the fixed sentence' );
	}
	if ( false === strpos( $html, 'class="rs-embed-empty__title">Acme Studio</p>' ) ) {
		throw new RuntimeException( 'white-label title missing' );
	}
	if ( false !== strpos( $html, '<img' ) ) {
		throw new RuntimeException( 'missing logo should not render an image' );
	}

	rs_clear_product_config_filter();
}

function test_editor_empty_card_uses_the_same_copy_and_classes(): void {
	$plugin_root = dirname( __DIR__, 2 );
	$edit        = file_get_contents( $plugin_root . '/src/recording-studio-widget/edit.js' );
	$built       = file_get_contents( $plugin_root . '/build/recording-studio-widget/index.js' );
	if ( false === $edit || false === $built ) {
		throw new RuntimeException( 'could not read editor sources' );
	}

	$body = 'Pick a page on the block settings to embed.';
	foreach ( array( 'edit.js' => $edit, 'built editor script' => $built ) as $label => $source ) {
		if ( false === strpos( $source, $body ) ) {
			throw new RuntimeException( $label . ' missing the empty card body' );
		}
		if ( false === strpos( $source, 'rs-embed-empty__logo' ) || false === strpos( $source, 'rs-embed-empty__title' ) ) {
			throw new RuntimeException( $label . ' missing empty card classes' );
		}
		if ( false !== strpos( $source, 'preview the %s embed' ) || false !== strpos( $source, 'Pick a page in the block settings to preview' ) ) {
			throw new RuntimeException( $label . ' still renders the old empty paragraph' );
		}
	}
	if ( false === strpos( $edit, "productText( 'logoUrl' )" ) ) {
		throw new RuntimeException( 'editor empty card must read logoUrl from ProductConfig' );
	}
}

function test_embed_empty_styles_enqueue_for_editor_and_front(): void {
	$GLOBALS['rs_test_enqueued_styles'] = array();
	recording_studio_plugin_demo_enqueue_embed_empty_styles();
	$enqueued = $GLOBALS['rs_test_enqueued_styles'];
	if ( 2 !== count( $enqueued ) ) {
		throw new RuntimeException( 'expected look tokens and the empty card stylesheet' );
	}
	if ( 'recording-studio-plugin-demo-tokens' !== $enqueued[0]['handle'] ) {
		throw new RuntimeException( 'empty card must load look tokens first' );
	}
	if ( 'recording-studio-plugin-demo-embed-empty' !== $enqueued[1]['handle'] ) {
		throw new RuntimeException( 'unexpected empty card handle ' . (string) $enqueued[1]['handle'] );
	}
	if ( false === strpos( (string) $enqueued[1]['src'], 'assets/admin/embed-empty.css' ) ) {
		throw new RuntimeException( 'empty card stylesheet src mismatch' );
	}

	$plugin = file_get_contents( dirname( __DIR__, 2 ) . '/recording-studio-widget.php' );
	if ( false === $plugin || false === strpos( $plugin, "add_action( 'enqueue_block_assets', 'recording_studio_plugin_demo_enqueue_embed_empty_styles' )" ) ) {
		throw new RuntimeException( 'empty card styles must enqueue on enqueue_block_assets' );
	}

	$css = file_get_contents( dirname( __DIR__, 2 ) . '/assets/admin/embed-empty.css' );
	if ( false === $css ) {
		throw new RuntimeException( 'could not read embed-empty.css' );
	}
	if ( false === strpos( $css, 'background: #fff' ) || false === strpos( $css, 'border: 1px solid var(--rs-line)' ) || false === strpos( $css, 'border-radius: 0.75rem' ) ) {
		throw new RuntimeException( 'empty card css missing the settings card border' );
	}
	if ( false === strpos( $css, 'object-fit: contain' ) || false === strpos( $css, 'var(--rs-muted)' ) || false === strpos( $css, 'var(--rs-charcoal)' ) ) {
		throw new RuntimeException( 'empty card css missing logo fit or text tokens' );
	}
	if ( false !== strpos( $css, 'oklch(' ) ) {
		throw new RuntimeException( 'empty card css must use shared tokens instead of a second palette' );
	}
}
