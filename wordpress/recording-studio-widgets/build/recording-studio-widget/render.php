<?php
/**
 * Server-rendered placeholder for the RecordingStudio Widget block.
 *
 * @package RecordingStudio
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<p <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo esc_html( recording_studio_widget_placeholder_text() ); ?>
</p>
