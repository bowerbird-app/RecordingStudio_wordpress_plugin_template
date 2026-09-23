<?php

declare(strict_types=1);

use RecordingStudio\ConnectStatus;
use RecordingStudio\SettingsPage;

if ( ! function_exists( 'plugins_url' ) ) {
	function plugins_url( $path = '', $plugin = '' ) {
		return (string) $path;
	}
}

if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		if ( ! isset( $GLOBALS['rs_test_enqueued_styles'] ) || ! is_array( $GLOBALS['rs_test_enqueued_styles'] ) ) {
			$GLOBALS['rs_test_enqueued_styles'] = array();
		}
		$GLOBALS['rs_test_enqueued_styles'][] = array(
			'handle' => $handle,
			'src'    => $src,
		);
	}
}

require_once dirname( __DIR__, 2 ) . '/includes/settings-page.php';

function rs_settings_look_markup( bool $connected ): string {
	rs_clear_product_config_filter();
	return SettingsPage::markup(
		array(
			'client_id'     => 'legacy-shared-id',
			'client_secret' => 'super-secret',
		),
		'',
		new ConnectStatus( $connected ),
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start',
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect',
		'',
		rs_open_registration_offer()
	);
}

function rs_settings_look_button( string $html, string $label ): string {
	$pattern = '/<(button|a)\b[^>]*>' . preg_quote( $label, '/' ) . '<\/\1>/';
	if ( 1 !== preg_match( $pattern, $html, $match ) ) {
		throw new RuntimeException( 'missing button ' . $label );
	}
	return $match[0];
}

function test_settings_look_disconnected_reads_as_flatpack_form(): void {
	$html     = rs_settings_look_markup( false );
	$save     = rs_settings_look_button( $html, 'Save settings' );
	$probe    = rs_settings_look_button( $html, 'Test connection' );
	$login    = rs_settings_look_button( $html, 'Login' );
	$register = rs_settings_look_button( $html, 'Register' );

	if ( false === strpos( $html, 'rs-settings-fp' ) ) {
		throw new RuntimeException( 'disconnected markup missing rs-settings-fp' );
	}
	if ( false === strpos( $html, 'rs-settings-fp__card' ) ) {
		throw new RuntimeException( 'disconnected markup missing rs-settings-fp__card' );
	}
	if ( false === strpos( $html, 'The key from your app.' ) ) {
		throw new RuntimeException( 'disconnected markup missing API key help' );
	}
	if ( false === strpos( $html, 'The matching secret. Leave blank to clear it.' ) ) {
		throw new RuntimeException( 'disconnected markup missing secret help' );
	}
	if ( false === strpos( $save, 'rs-settings-fp__button--primary' ) || false === strpos( $save, 'Save settings' ) ) {
		throw new RuntimeException( 'Save settings button missing primary class' );
	}
	if ( false === strpos( $probe, 'rs-settings-fp__button--outline' ) || false === strpos( $probe, 'Test connection' ) ) {
		throw new RuntimeException( 'Test connection button missing outline class' );
	}
	if ( false === strpos( $login, 'rs-settings-fp__button--primary' ) ) {
		throw new RuntimeException( 'Login must use the primary button' );
	}
	if ( false === strpos( $register, 'rs-settings-fp__button--outline' ) || 0 !== strpos( $register, '<a ' ) ) {
		throw new RuntimeException( 'Register must use the secondary link' );
	}
	if ( false !== strpos( $login, 'button-primary' ) || false !== strpos( $register, 'button-primary' ) ) {
		throw new RuntimeException( 'Login or Register still uses button-primary' );
	}
	$start = 'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start';
	if ( false === strpos( $login, $start ) ) {
		throw new RuntimeException( 'Login must start Connect' );
	}
	if ( false === strpos( $register, 'href="https://studio.example/users/sign_up"' ) || false !== strpos( $register, $start ) ) {
		throw new RuntimeException( 'Register must open host signup and must not start Connect' );
	}
	$details_end = strpos( $html, '</details>' );
	$save_pos    = strpos( $html, 'Save settings' );
	$secret_pos  = strpos( $html, 'Secret key' );
	if ( false === $details_end || false === $save_pos || false === $secret_pos || $secret_pos > $save_pos || $save_pos > $details_end ) {
		throw new RuntimeException( 'Save settings must sit inside Advanced after the secret field' );
	}
	if ( false !== strpos( $html, 'Connect to Recording Studio' ) ) {
		throw new RuntimeException( 'disconnected markup should not show Connect to Recording Studio' );
	}
}

function test_settings_look_connected_buttons_stay_outline(): void {
	$html = rs_settings_look_markup( true );
	$disconnect = rs_settings_look_button( $html, 'Disconnect' );
	$again      = rs_settings_look_button( $html, 'Connect again' );

	if ( false === strpos( $disconnect, 'rs-settings-fp__button--outline' ) ) {
		throw new RuntimeException( 'Disconnect button missing outline class' );
	}
	if ( false === strpos( $again, 'rs-settings-fp__button--outline' ) ) {
		throw new RuntimeException( 'Connect again button missing outline class' );
	}
	if ( false === strpos( $html, 'notice notice-success' ) ) {
		throw new RuntimeException( 'connected status missing notice notice-success' );
	}
	if ( false === strpos( $html, 'rs-settings-fp__alert' ) ) {
		throw new RuntimeException( 'connected status missing rs-settings-fp__alert' );
	}
	if ( false !== strpos( $html, '>Login</button>' ) || false !== strpos( $html, '>Register</a>' ) || false !== strpos( $html, '>Register</button>' ) ) {
		throw new RuntimeException( 'connected markup should keep Disconnect and Connect again' );
	}
}

function test_settings_look_css_stays_under_rs_settings_fp(): void {
	$path = dirname( __DIR__, 2 ) . '/assets/admin/settings.css';
	$css  = file_get_contents( $path );
	if ( false === $css ) {
		throw new RuntimeException( 'could not read settings.css' );
	}
	if ( false === strpos( $css, '.rs-settings-fp' ) ) {
		throw new RuntimeException( 'settings.css missing .rs-settings-fp' );
	}
	if ( false === strpos( $css, 'oklch(0.3211 0 0)' ) ) {
		throw new RuntimeException( 'settings.css missing charcoal oklch(0.3211 0 0)' );
	}
	if ( false === strpos( $css, '--rs-error-surface' ) ) {
		throw new RuntimeException( 'settings.css missing tinted error alert' );
	}

	$stripped = preg_replace( '/\/\*.*?\*\//s', '', $css );
	if ( ! is_string( $stripped ) ) {
		throw new RuntimeException( 'could not strip settings.css comments' );
	}

	$chunks = explode( '{', $stripped );
	array_pop( $chunks );
	foreach ( $chunks as $chunk ) {
		$close    = strrpos( $chunk, '}' );
		$selector = false === $close ? $chunk : substr( $chunk, $close + 1 );
		$parts    = preg_split( '/\s*,\s*/', trim( $selector ) );
		if ( ! is_array( $parts ) ) {
			throw new RuntimeException( 'could not split selector ' . trim( $selector ) );
		}
		foreach ( $parts as $part ) {
			if ( '' === $part ) {
				continue;
			}
			if ( false === strpos( $part, '.rs-settings-fp' ) ) {
				throw new RuntimeException( 'selector missing .rs-settings-fp: ' . $part );
			}
		}
	}
}

function test_settings_look_error_notice_uses_the_alert(): void {
	rs_clear_product_config_filter();
	$html = SettingsPage::markup(
		array(
			'client_id'     => 'legacy-shared-id',
			'client_secret' => 'super-secret',
		),
		'probe_failed',
		new ConnectStatus( false ),
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start',
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect'
	);

	if ( false === strpos( $html, 'notice notice-error is-dismissible rs-settings-fp__alert' ) ) {
		throw new RuntimeException( 'probe failure should use an error alert' );
	}
	if ( false === strpos( $html, 'The host did not accept those API keys. Check the Advanced API key and secret.' ) ) {
		throw new RuntimeException( 'probe failure missing the host message' );
	}
}

function test_settings_styles_enqueue_only_on_the_settings_screen(): void {
	$GLOBALS['rs_test_enqueued_styles'] = array();
	recording_studio_plugin_demo_enqueue_settings_styles( 'index.php' );
	if ( array() !== $GLOBALS['rs_test_enqueued_styles'] ) {
		throw new RuntimeException( 'settings styles enqueued on the wrong screen' );
	}

	$GLOBALS['rs_test_enqueued_styles'] = array();
	recording_studio_plugin_demo_enqueue_settings_styles( 'settings_page_recording-studio-plugin-demo' );
	$enqueued = $GLOBALS['rs_test_enqueued_styles'];
	if ( 1 !== count( $enqueued ) ) {
		throw new RuntimeException( 'expected one settings stylesheet' );
	}
	if ( 'recording-studio-plugin-demo-settings' !== $enqueued[0]['handle'] ) {
		throw new RuntimeException( 'unexpected stylesheet handle ' . (string) $enqueued[0]['handle'] );
	}
	if ( false === strpos( (string) $enqueued[0]['src'], 'assets/admin/settings.css' ) ) {
		throw new RuntimeException( 'stylesheet src missing assets/admin/settings.css' );
	}
}

function test_settings_load_shows_register_only_when_options_allow_signup(): void {
	\RecordingStudio\ConnectTokens::clear();
	\RecordingStudio\ConnectOptions::set_test_get(
		static function (): array {
			return array(
				'status' => 200,
				'body'   => array(
					'registration' => false,
				),
			);
		}
	);

	ob_start();
	recording_studio_plugin_demo_render_settings_page();
	$hidden = (string) ob_get_clean();
	if ( false !== strpos( $hidden, '>Register</a>' ) ) {
		throw new RuntimeException( 'settings load must hide Register when registration is false' );
	}
	if ( false === strpos( $hidden, '>Login</button>' ) || false === strpos( $hidden, 'rs-settings-fp__button--primary' ) ) {
		throw new RuntimeException( 'settings load must keep Login as the primary button' );
	}

	\RecordingStudio\ConnectOptions::set_test_get(
		static function (): array {
			return array(
				'status' => 200,
				'body'   => array(
					'registration'     => true,
					'registration_url' => 'https://studio.example/users/sign_up',
				),
			);
		}
	);
	ob_start();
	recording_studio_plugin_demo_render_settings_page();
	$shown = (string) ob_get_clean();
	\RecordingStudio\ConnectOptions::set_test_get( null );

	if ( false === strpos( $shown, 'href="https://studio.example/users/sign_up"' ) || false === strpos( $shown, 'target="_blank"' ) ) {
		throw new RuntimeException( 'settings load must open host signup when registration is allowed' );
	}
	if ( false === strpos( $shown, 'rs-settings-fp__button--outline' ) ) {
		throw new RuntimeException( 'settings load must style Register as the secondary button' );
	}
}
