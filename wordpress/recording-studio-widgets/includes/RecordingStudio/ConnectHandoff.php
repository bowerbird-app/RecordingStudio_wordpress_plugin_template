<?php

declare(strict_types=1);

namespace RecordingStudio;

final class ConnectHandoff {
	public const HEADING  = 'Taking you to Recording Studio to connect…';
	public const CONTINUE = 'Continue to Recording Studio';

	public static function markup( string $authorize_url ): string {
		$href = esc_url( $authorize_url );
		$js   = wp_json_encode( $authorize_url );
		if ( ! is_string( $js ) ) {
			$js = '""';
		}

		return '<!DOCTYPE html><html><head><meta charset="utf-8">'
			. '<meta http-equiv="refresh" content="1;url=' . $href . '">'
			. '<title>' . esc_html( self::HEADING ) . '</title>'
			. '<script>window.setTimeout(function(){location.replace(' . $js . ');},1000);</script>'
			. '</head><body>'
			. '<p>' . esc_html( self::HEADING ) . '</p>'
			. '<noscript><p><a href="' . $href . '">' . esc_html( self::CONTINUE ) . '</a></p></noscript>'
			. '</body></html>';
	}
}
