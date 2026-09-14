<?php
/**
 * Plugin Name:       RecordingStudio Widget
 * Plugin URI:        https://github.com/bowerbird-app/RecordingStudio_wordpress_plugin_template
 * Description:       Placeholder RecordingStudio Widget dynamic block. Install and activate it. It does not load live widgets yet.
 * Version:           0.1.0
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

require_once __DIR__ . '/includes/placeholder.php';

/**
 * Registers the RecordingStudio Widget block from the compiled metadata collection.
 */
function recording_studio_recording_studio_widget_block_init() {
	$manifest = __DIR__ . '/build/blocks-manifest.php';
	$build    = __DIR__ . '/build';

	if ( ! file_exists( $manifest ) || ! is_dir( $build ) ) {
		return;
	}

	wp_register_block_types_from_metadata_collection( $build, $manifest );
}
add_action( 'init', 'recording_studio_recording_studio_widget_block_init' );

/**
 * Adds a placeholder settings page under Settings.
 */
function recording_studio_widget_register_settings_page() {
	add_options_page(
		'RecordingStudio Widget',
		'RecordingStudio Widget',
		'manage_options',
		'recording-studio-widget',
		'recording_studio_widget_render_settings_page'
	);
}
add_action( 'admin_menu', 'recording_studio_widget_register_settings_page' );

/**
 * Renders the placeholder settings page.
 */
function recording_studio_widget_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="wrap">';
	echo '<h1>' . esc_html( get_admin_page_title() ) . '</h1>';
	echo '<p>' . esc_html__( 'This plugin is a placeholder. It does not connect to Recording Studio yet.', 'recording-studio-widget' ) . '</p>';
	echo '</div>';
}
