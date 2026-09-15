<?php
/**
 * Plugin Name:       WordPress Plugin Demo
 * Plugin URI:        https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template
 * Description:       Embeds Recording Studio pages from the wp_plugin_demo host API using the baked plugin SDK.
 * Version:           0.2.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Bowerbird
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       recording-studio-widget
 *
 * @package RecordingStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function recording_studio_widget_autoload( string $class_name ): void {
	$prefix = 'RecordingStudio\\';
	if ( strncmp( $class_name, $prefix, strlen( $prefix ) ) !== 0 ) {
		return;
	}

	$relative = substr( $class_name, strlen( $prefix ) );
	$file     = __DIR__ . '/includes/RecordingStudio/' . str_replace( '\\', '/', $relative ) . '.php';
	if ( is_readable( $file ) ) {
		require_once $file;
	}
}

spl_autoload_register( 'recording_studio_widget_autoload' );

require_once __DIR__ . '/includes/settings-page.php';
require_once __DIR__ . '/includes/rest-editor-preview.php';

function recording_studio_recording_studio_widget_block_init(): void {
	$manifest = __DIR__ . '/build/blocks-manifest.php';
	$build    = __DIR__ . '/build';

	if ( ! file_exists( $manifest ) || ! is_dir( $build ) ) {
		return;
	}

	\RecordingStudio\Assets::register_sdk();
	wp_register_block_types_from_metadata_collection( $build, $manifest );
}
add_action( 'init', 'recording_studio_recording_studio_widget_block_init' );

function recording_studio_widget_register_settings_page(): void {
	add_options_page(
		__( 'WordPress Plugin Demo', 'recording-studio-widget' ),
		__( 'WordPress Plugin Demo', 'recording-studio-widget' ),
		'manage_options',
		'recording-studio-plugin-demo',
		'recording_studio_plugin_demo_render_settings_page'
	);
}
add_action( 'admin_menu', 'recording_studio_widget_register_settings_page' );

add_action( 'rest_api_init', 'recording_studio_register_editor_preview_route' );
