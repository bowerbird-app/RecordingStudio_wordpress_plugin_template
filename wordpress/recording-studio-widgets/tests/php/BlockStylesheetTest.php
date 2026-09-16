<?php

declare(strict_types=1);

function test_block_does_not_register_a_stylesheet_handle(): void {
	$plugin_root = dirname( __DIR__, 2 );
	$block_json  = $plugin_root . '/src/recording-studio-widget/block.json';

	if ( ! is_readable( $block_json ) ) {
		throw new RuntimeException( 'src block.json is missing' );
	}

	$raw = file_get_contents( $block_json );
	if ( false === $raw ) {
		throw new RuntimeException( 'could not read src block.json' );
	}

	$decoded = json_decode( $raw, true );
	if ( ! is_array( $decoded ) ) {
		throw new RuntimeException( 'src block.json is not valid JSON' );
	}

	if ( array_key_exists( 'style', $decoded ) || array_key_exists( 'editorStyle', $decoded ) ) {
		throw new RuntimeException(
			'block.json must not register style or editorStyle; WP must pass through embed HTML/CSS as-is'
		);
	}
}

function test_source_block_style_scss_is_absent(): void {
	$plugin_root = dirname( __DIR__, 2 );
	$scss_path   = $plugin_root . '/src/recording-studio-widget/style.scss';

	if ( is_readable( $scss_path ) ) {
		throw new RuntimeException(
			'src style.scss must not exist; remove create-block chrome instead of shipping an empty stylesheet'
		);
	}
}

function test_built_block_stylesheet_is_absent_or_empty_of_chrome(): void {
	$plugin_root = dirname( __DIR__, 2 );
	$css_path    = $plugin_root . '/build/recording-studio-widget/style-index.css';

	if ( ! file_exists( $css_path ) ) {
		return;
	}

	if ( ! is_readable( $css_path ) ) {
		throw new RuntimeException( 'built style-index.css exists but is unreadable' );
	}

	$css = file_get_contents( $css_path );
	if ( false === $css ) {
		throw new RuntimeException( 'could not read built style-index.css' );
	}

	$normalized = strtolower( preg_replace( '/\s+/', '', $css ) );
	if ( '' === $normalized ) {
		throw new RuntimeException(
			'stale empty style-index.css remains in build; delete it so the ZIP does not ship a leftover stylesheet'
		);
	}

	if ( false !== strpos( $normalized, 'background-color:#21759b' ) ) {
		throw new RuntimeException(
			'built block stylesheet still paints create-block teal (#21759b) over embed payload'
		);
	}

	if ( preg_match( '/\.wp-block-recording-studio-recording-studio-widget\{[^}]*\bcolor:#fff\b/', $normalized ) ) {
		throw new RuntimeException(
			'built block stylesheet still forces white text on the block wrapper'
		);
	}

	throw new RuntimeException(
		'built style-index.css must not ship; remove the block style pipeline and rebuild'
	);
}
