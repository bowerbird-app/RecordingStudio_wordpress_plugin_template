<?php

declare(strict_types=1);

/**
 * WordPress must pass through host embed HTML/CSS as-is.
 * create-block scaffold chrome on the block wrapper overrides payload presentation.
 */
function test_built_block_stylesheet_does_not_paint_scaffold_chrome(): void {
	$plugin_root = dirname( __DIR__, 2 );
	$css_path    = $plugin_root . '/build/recording-studio-widget/style-index.css';

	if ( ! is_readable( $css_path ) ) {
		return;
	}

	$css = file_get_contents( $css_path );
	if ( false === $css ) {
		throw new RuntimeException( 'could not read built block stylesheet' );
	}

	$css = strtolower( preg_replace( '/\s+/', '', $css ) );

	if ( false !== strpos( $css, 'background-color:#21759b' ) ) {
		throw new RuntimeException(
			'built block stylesheet still paints create-block teal (#21759b) over embed payload'
		);
	}

	if ( preg_match( '/\.wp-block-recording-studio-recording-studio-widget\{[^}]*\bcolor:#fff\b/', $css ) ) {
		throw new RuntimeException(
			'built block stylesheet still forces white text on the block wrapper'
		);
	}
}

function test_source_block_style_scss_does_not_paint_scaffold_chrome(): void {
	$plugin_root = dirname( __DIR__, 2 );
	$scss_path   = $plugin_root . '/src/recording-studio-widget/style.scss';

	if ( ! is_readable( $scss_path ) ) {
		return;
	}

	$scss = file_get_contents( $scss_path );
	if ( false === $scss ) {
		throw new RuntimeException( 'could not read source style.scss' );
	}

	if ( preg_match( '/background-color\s*:\s*#21759b/i', $scss ) ) {
		throw new RuntimeException(
			'source style.scss still declares create-block teal (#21759b)'
		);
	}

	if ( preg_match( '/\.wp-block-recording-studio-recording-studio-widget\s*\{[^}]*\bcolor\s*:\s*#fff\b/is', $scss ) ) {
		throw new RuntimeException(
			'source style.scss still forces white text on the block wrapper'
		);
	}
}
