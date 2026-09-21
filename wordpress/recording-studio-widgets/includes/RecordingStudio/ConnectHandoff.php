<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ConnectHandoff {
	public const HEADING  = 'Taking you to Recording Studio to connect…';
	public const CONTINUE = 'Continue to Recording Studio';

	public static function print( string $connect_url ): void {
		echo '<!DOCTYPE html><html><head><meta charset="utf-8">';
		echo '<meta http-equiv="refresh" content="1;url=' . esc_url( $connect_url ) . '">';
		echo '<title>' . esc_html( self::HEADING ) . '</title>';
		echo '<script>window.setTimeout(function(){location.replace(' . wp_json_encode( $connect_url ) . ');},1000);</script>';
		echo '</head><body>';
		echo '<p>' . esc_html( self::HEADING ) . '</p>';
		echo '<noscript><p><a href="' . esc_url( $connect_url ) . '">' . esc_html( self::CONTINUE ) . '</a></p></noscript>';
		echo '</body></html>';
	}
}
