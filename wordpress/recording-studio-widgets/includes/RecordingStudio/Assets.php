<?php

declare(strict_types=1);

namespace RecordingStudio;

final class Assets {
	public const SDK_SCRIPT_HANDLE = 'recording-studio-plugin-sdk';
	public const SDK_STYLE_HANDLE  = 'recording-studio-plugin-sdk';

	public static function enqueue_sdk(): void {
		self::register_sdk();
		wp_enqueue_style( self::SDK_STYLE_HANDLE );
		wp_enqueue_script( self::SDK_SCRIPT_HANDLE );
	}

	public static function register_sdk(): void {
		$plugin_root = dirname( __DIR__, 2 );
		$script_path = $plugin_root . '/build/sdk/recording-studio-plugin-sdk.js';
		$style_path  = $plugin_root . '/build/sdk/recording-studio-plugin-sdk.css';

		if ( ! file_exists( $script_path ) ) {
			return;
		}

		$script_url = plugins_url( 'build/sdk/recording-studio-plugin-sdk.js', $plugin_root . '/recording-studio-widget.php' );
		$style_url  = plugins_url( 'build/sdk/recording-studio-plugin-sdk.css', $plugin_root . '/recording-studio-widget.php' );

		wp_register_script(
			self::SDK_SCRIPT_HANDLE,
			$script_url,
			array(),
			(string) filemtime( $script_path ),
			true
		);

		if ( file_exists( $style_path ) ) {
			wp_register_style(
				self::SDK_STYLE_HANDLE,
				$style_url,
				array(),
				(string) filemtime( $style_path )
			);
		}
	}
}
