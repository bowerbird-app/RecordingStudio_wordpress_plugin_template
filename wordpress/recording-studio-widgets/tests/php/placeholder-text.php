<?php
/**
 * Asserts the dynamic block placeholder without booting WordPress.
 */

$plugin_root = dirname( __DIR__, 2 );
require $plugin_root . '/includes/placeholder.php';

$expected = 'RecordingStudio Widget placeholder. This block does not load a live widget yet.';

if ( recording_studio_widget_placeholder_text() !== $expected ) {
	fwrite( STDERR, "placeholder helper returned unexpected text\n" );
	exit( 1 );
}

$render = file_get_contents( $plugin_root . '/src/recording-studio-widget/render.php' );
$edit   = file_get_contents( $plugin_root . '/src/recording-studio-widget/edit.js' );
$plugin = file_get_contents( $plugin_root . '/recording-studio-widget.php' );

if ( $render === false || $edit === false || $plugin === false ) {
	fwrite( STDERR, "could not read plugin source files\n" );
	exit( 1 );
}

if ( ! str_contains( $render, 'recording_studio_widget_placeholder_text()' ) ) {
	fwrite( STDERR, "render.php must call recording_studio_widget_placeholder_text()\n" );
	exit( 1 );
}

if ( ! str_contains( $edit, $expected ) ) {
	fwrite( STDERR, "edit.js must show the same placeholder text\n" );
	exit( 1 );
}

if ( ! str_contains( $plugin, 'Plugin Name:       RecordingStudio Widget' ) ) {
	fwrite( STDERR, "plugin header must name RecordingStudio Widget\n" );
	exit( 1 );
}

if ( ! str_contains( $plugin, 'recording_studio_widget_register_settings_page' ) ) {
	fwrite( STDERR, "plugin must register the settings page placeholder\n" );
	exit( 1 );
}

echo "placeholder text ok\n";
