<?php

declare(strict_types=1);

use RecordingStudio\ConnectOptions;
use RecordingStudio\ConnectStatus;
use RecordingStudio\PluginSettings;
use RecordingStudio\ProductConfig;
use RecordingStudio\RegistrationOffer;
use RecordingStudio\SettingsForm;
use RecordingStudio\SettingsPage;

function rs_open_registration_offer(): RegistrationOffer {
	return RegistrationOffer::from_body(
		array(
			'registration'     => true,
			'registration_url' => 'https://studio.example/users/sign_up',
		)
	);
}

function rs_clear_product_config_filter(): void {
	unset( $GLOBALS['rs_test_filters']['recording_studio_product_config'] );
}

function rs_product_config_markup( bool $connected, string $notice = '' ): string {
	return SettingsPage::markup(
		array(
			'client_id'     => 'legacy-shared-id',
			'client_secret' => 'super-secret',
		),
		$notice,
		new ConnectStatus( $connected ),
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start',
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect',
		'',
		rs_open_registration_offer()
	);
}

function test_product_config_defaults_and_disconnected_markup(): void {
	rs_clear_product_config_filter();

	$expected = array(
		'name'                 => 'WP Template Demo',
		'description'          => 'Shows a page from your studio.',
		'oauth_connect'        => true,
		'api_keys'             => false,
		'register'             => true,
		'login_button_text'    => 'Login',
		'register_button_text' => 'Register',
		'logo'                 => 'build/brand/product-logo-red.jpg',
	);
	if ( $expected !== ProductConfig::all() ) {
		throw new RuntimeException( 'ProductConfig::all() defaults mismatch' );
	}

	$html = rs_product_config_markup( false );
	if ( false === strpos( $html, 'WP Template Demo' ) ) {
		throw new RuntimeException( 'disconnected markup missing default name' );
	}
	$logo_url = 'http://localhost:8888/wp-content/plugins/recording-studio-widgets/build/brand/product-logo-red.jpg';
	$logo_pos = strpos( $html, '<img class="rs-settings-fp__logo" src="' . $logo_url . '" alt="" width="32" height="32" />' );
	$name_pos = strpos( $html, 'WP Template Demo' );
	if ( false === $logo_pos || false === $name_pos || $logo_pos > $name_pos ) {
		throw new RuntimeException( 'settings heading should show the product logo before the title' );
	}
	if ( false === strpos( $html, '>Login</button>' ) || false === strpos( $html, '>Register</a>' ) ) {
		throw new RuntimeException( 'disconnected markup missing Login and Register' );
	}
	if ( false !== strpos( $html, 'Connect to Recording Studio' ) ) {
		throw new RuntimeException( 'disconnected markup should not show Connect to Recording Studio' );
	}
	if ( false !== strpos( $html, '<summary>Advanced</summary>' ) ) {
		throw new RuntimeException( 'demo markup should hide Advanced while API_KEYS is false' );
	}
	if ( false !== strpos( $html, 'Connect via API key' ) || false !== strpos( $html, 'name="rs_api_key"' ) ) {
		throw new RuntimeException( 'demo markup should hide Connect via API key' );
	}
	if ( false !== strpos( $html, 'Save settings' ) || false !== strpos( $html, 'Test connection' ) ) {
		throw new RuntimeException( 'demo markup should hide Save settings and Test connection' );
	}

	rs_clear_product_config_filter();
}

function test_product_config_oauth_only_hides_advanced(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['api_keys'] = false;
			return $config;
		}
	);

	$html = rs_product_config_markup( false );
	if ( false === strpos( $html, '>Login</button>' ) || false === strpos( $html, '>Register</a>' ) ) {
		throw new RuntimeException( 'oauth-only markup missing Login and Register' );
	}
	if ( false !== strpos( $html, '<summary>Advanced</summary>' ) ) {
		throw new RuntimeException( 'oauth-only markup should hide Advanced' );
	}
	if ( false !== strpos( $html, 'name="rs_api_key"' ) ) {
		throw new RuntimeException( 'oauth-only markup should hide API key field' );
	}
	if ( false !== strpos( $html, 'Secret key' ) ) {
		throw new RuntimeException( 'oauth-only markup should hide Secret key' );
	}
	if ( false !== strpos( $html, 'Save settings' ) || false !== strpos( $html, 'Test connection' ) ) {
		throw new RuntimeException( 'oauth-only markup should hide Save and Test' );
	}

	rs_clear_product_config_filter();
}

function test_product_config_api_only_hides_connect(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['oauth_connect'] = false;
			$config['api_keys']      = true;
			return $config;
		}
	);

	$html = rs_product_config_markup( true, 'connected' );
	if ( false !== strpos( $html, 'Connect to Recording Studio' ) || false !== strpos( $html, '>Login</button>' ) || false !== strpos( $html, '>Register</a>' ) ) {
		throw new RuntimeException( 'api-only markup should hide Login, Register, and Connect' );
	}
	if ( false !== strpos( $html, 'Disconnect' ) ) {
		throw new RuntimeException( 'api-only markup should hide Disconnect' );
	}
	if ( false !== strpos( $html, 'Connect again' ) ) {
		throw new RuntimeException( 'api-only markup should hide Connect again' );
	}
	if ( false !== strpos( $html, 'This site is connected.' ) ) {
		throw new RuntimeException( 'api-only markup should hide connected notice' );
	}
	if ( false === strpos( $html, '<summary>Advanced</summary>' ) ) {
		throw new RuntimeException( 'api-only markup missing Advanced' );
	}
	if ( false === strpos( $html, 'name="rs_api_key"' ) ) {
		throw new RuntimeException( 'api-only markup missing API key field' );
	}
	$details_end = strpos( $html, '</details>' );
	$save_pos    = strpos( $html, 'Save settings' );
	$test_pos    = strpos( $html, 'Test connection' );
	if ( false === $save_pos || false === $test_pos || false === $details_end || $save_pos > $details_end || $test_pos > $details_end ) {
		throw new RuntimeException( 'api-only Save and Test must sit inside Advanced' );
	}

	rs_clear_product_config_filter();
}

function test_product_config_button_texts_trim_and_fall_back(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['login_button_text']    = '  Sign in  ';
			$config['register_button_text'] = '   ';
			$config['name']                 = 12;
			return $config;
		}
	);

	$all = ProductConfig::all();
	if ( 'Sign in' !== $all['login_button_text'] ) {
		throw new RuntimeException( 'login button text should trim, got ' . $all['login_button_text'] );
	}
	if ( 'Register' !== $all['register_button_text'] ) {
		throw new RuntimeException( 'blank register button text should fall back to Register' );
	}
	if ( 'WP Template Demo' !== $all['name'] ) {
		throw new RuntimeException( 'non-string name should fall back to the default' );
	}

	$html = SettingsPage::markup(
		array(),
		'',
		new ConnectStatus( false ),
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start',
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect',
		'',
		rs_open_registration_offer()
	);
	if ( false === strpos( $html, '>Sign in</button>' ) || false === strpos( $html, '>Register</a>' ) ) {
		throw new RuntimeException( 'disconnected buttons should use the filtered texts' );
	}
	if ( false !== strpos( $html, '>Login</button>' ) ) {
		throw new RuntimeException( 'disconnected markup still shows the default Login label' );
	}

	rs_clear_product_config_filter();
}

function test_product_config_filter_overrides_name(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['name'] = 'Harbor Widgets';
			return $config;
		}
	);

	if ( 'Harbor Widgets' !== SettingsPage::title() ) {
		throw new RuntimeException( 'title should use the filtered name, got ' . SettingsPage::title() );
	}

	$html = rs_product_config_markup( false );
	if ( false === strpos( $html, 'Harbor Widgets' ) ) {
		throw new RuntimeException( 'markup missing filtered name' );
	}
	if ( false !== strpos( $html, 'WP Template Demo' ) ) {
		throw new RuntimeException( 'markup still shows the default name' );
	}

	rs_clear_product_config_filter();
}

function test_product_config_omits_posted_keys_when_api_keys_off(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['api_keys'] = false;
			return $config;
		}
	);

	$_POST['rs_api_key']       = 'posted-key';
	$_POST['rs_client_secret'] = 'posted-secret';

	$posted = SettingsForm::read_post();
	if ( array_key_exists( 'api_key', $posted ) || array_key_exists( 'client_secret', $posted ) ) {
		throw new RuntimeException( 'read_post must omit keys when api_keys is false' );
	}

	update_option(
		PluginSettings::OPTION_KEY,
		array(
			'api_key'       => 'kept-key',
			'client_secret' => 'kept-secret',
		)
	);

	$merged = PluginSettings::validate_and_merge( SettingsForm::read_post() );
	if ( 'kept-key' !== $merged->api_key ) {
		throw new RuntimeException( 'stored api key was overwritten, got ' . $merged->api_key );
	}
	if ( 'kept-secret' !== $merged->client_secret ) {
		throw new RuntimeException( 'stored secret was overwritten, got ' . $merged->client_secret );
	}

	unset( $_POST['rs_api_key'], $_POST['rs_client_secret'] );
	rs_clear_product_config_filter();
}

function test_product_config_empty_secret_clears_when_api_keys_on(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['api_keys'] = true;
			return $config;
		}
	);

	update_option(
		PluginSettings::OPTION_KEY,
		array(
			'api_key'       => 'kept-key',
			'client_secret' => 'kept-secret',
		)
	);

	$_POST['rs_api_key']       = 'kept-key';
	$_POST['rs_client_secret'] = '';

	$merged = PluginSettings::validate_and_merge( SettingsForm::read_post() );
	if ( '' !== $merged->client_secret ) {
		throw new RuntimeException( 'empty secret should still clear when api_keys is true' );
	}

	unset( $_POST['rs_api_key'], $_POST['rs_client_secret'] );
	rs_clear_product_config_filter();
}

function test_product_config_register_is_a_boolean_and_drops_link_to(): void {
	rs_clear_product_config_filter();
	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['register'] = false;
			$config['link_to']  = 'https://studio.example/users/sign_up';
			return $config;
		}
	);

	$all = ProductConfig::all();
	if ( true === $all['register'] ) {
		throw new RuntimeException( 'register filter false should hide the button' );
	}
	if ( array_key_exists( 'link_to', $all ) ) {
		throw new RuntimeException( 'product config must not expose link_to' );
	}

	$html = SettingsPage::markup(
		array(),
		'',
		new ConnectStatus( false ),
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start',
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect',
		'',
		rs_open_registration_offer()
	);
	if ( false !== strpos( $html, '>Register</a>' ) ) {
		throw new RuntimeException( 'register false must hide Register even when the host allows signup' );
	}
	if ( false === strpos( $html, 'rs-settings-fp__button--primary' ) || false === strpos( $html, '>Login</button>' ) ) {
		throw new RuntimeException( 'Login stays the primary button when Register is hidden' );
	}

	rs_clear_product_config_filter();
}

function test_product_config_logo_ships_the_red_jpeg_to_the_block(): void {
	rs_clear_product_config_filter();
	$plugin_root = dirname( __DIR__, 2 );
	$relative    = ProductConfig::all()['logo'];
	if ( 'build/brand/product-logo-red.jpg' !== $relative ) {
		throw new RuntimeException( 'logo path should be the shipped brand file, got ' . $relative );
	}

	$bytes = file_get_contents( $plugin_root . '/' . $relative );
	if ( ! is_string( $bytes ) || "\xFF\xD8\xFF" !== substr( $bytes, 0, 3 ) ) {
		throw new RuntimeException( 'shipped logo is not a jpeg' );
	}
	$source = file_get_contents( $plugin_root . '/assets/brand/product-logo-red.jpg' );
	if ( $source !== $bytes ) {
		throw new RuntimeException( 'build logo should match assets/brand/product-logo-red.jpg' );
	}

	add_filter(
		'recording_studio_product_config',
		static function ( array $config ): array {
			$config['logo'] = 'https://evil.example/logo.jpg';
			return $config;
		}
	);
	if ( 'build/brand/product-logo-red.jpg' !== ProductConfig::all()['logo'] ) {
		throw new RuntimeException( 'a remote logo path should fall back to the shipped file' );
	}
	rs_clear_product_config_filter();

	$url = ProductConfig::logo_url();
	if ( 'http://localhost:8888/wp-content/plugins/recording-studio-widgets/build/brand/product-logo-red.jpg' !== $url ) {
		throw new RuntimeException( 'logo url mismatch, got ' . $url );
	}

	$GLOBALS['rs_test_inline_scripts'] = array();
	ProductConfig::enqueue_editor_config();
	$inline = $GLOBALS['rs_test_inline_scripts'][0] ?? null;
	if ( ! is_array( $inline ) ) {
		throw new RuntimeException( 'editor config was not queued' );
	}
	if ( ProductConfig::EDITOR_SCRIPT_HANDLE !== $inline['handle'] || 'before' !== $inline['position'] ) {
		throw new RuntimeException( 'editor config must run before the block script' );
	}
	$data = (string) $inline['data'];
	if ( false === strpos( $data, 'window.recordingStudioProductConfig = ' ) ) {
		throw new RuntimeException( 'editor config missing the product global' );
	}
	$json = substr( $data, strlen( 'window.recordingStudioProductConfig = ' ) );
	$json = rtrim( $json, ';' );
	$decoded = json_decode( $json, true );
	if ( ! is_array( $decoded ) ) {
		throw new RuntimeException( 'editor config was not JSON' );
	}
	if ( 'WP Template Demo' !== ( $decoded['name'] ?? '' ) ) {
		throw new RuntimeException( 'editor config name mismatch, got ' . (string) ( $decoded['name'] ?? '' ) );
	}
	if ( 'Shows a page from your studio.' !== ( $decoded['description'] ?? '' ) ) {
		throw new RuntimeException( 'editor config description mismatch, got ' . (string) ( $decoded['description'] ?? '' ) );
	}
	$logo_url = (string) ( $decoded['logoUrl'] ?? '' );
	if ( 'http://localhost:8888/wp-content/plugins/recording-studio-widgets/build/brand/product-logo-red.jpg' !== $logo_url ) {
		throw new RuntimeException( 'editor config logo url mismatch, got ' . $logo_url );
	}

	$metadata = ProductConfig::filter_block_metadata(
		array(
			'name'        => 'recording-studio/recording-studio-widget',
			'title'       => 'WordPress Plugin Demo',
			'description' => 'Embeds a Recording Studio page from the wp_plugin_demo host API. Server-rendered payload; SDK mounts on the front.',
		)
	);
	if ( 'WP Template Demo' !== $metadata['title'] || 'Shows a page from your studio.' !== $metadata['description'] ) {
		throw new RuntimeException( 'block metadata should take the product name and description' );
	}
	$other = ProductConfig::filter_block_metadata(
		array(
			'name'  => 'core/paragraph',
			'title' => 'Paragraph',
		)
	);
	if ( 'Paragraph' !== $other['title'] ) {
		throw new RuntimeException( 'block metadata filter should ignore other blocks' );
	}
}

function test_register_follows_connect_options_and_fails_closed(): void {
	$settings = PluginSettings::from_storage_array( array() );
	$seen     = '';
	ConnectOptions::set_test_get(
		static function ( string $url ) use ( &$seen ): array {
			$seen = $url;
			return array(
				'status' => 200,
				'body'   => array(
					'registration' => false,
				),
			);
		}
	);

	$hidden = ConnectOptions::for_settings( $settings );
	if ( 'http://localhost:3000/recording_studio_oauth/connect/options?client_id=rsoauth_id_wordpress' !== $seen ) {
		throw new RuntimeException( 'connect options url mismatch, got ' . $seen );
	}
	if ( $hidden->visible_with( true ) ) {
		throw new RuntimeException( 'registration false should hide Register' );
	}

	ConnectOptions::set_test_get(
		static function (): array {
			return array(
				'status' => 500,
				'body'   => array(
					'registration'     => true,
					'registration_url' => 'https://studio.example/users/sign_up',
				),
			);
		}
	);
	if ( ConnectOptions::for_settings( $settings )->visible_with( true ) ) {
		throw new RuntimeException( 'a failed options fetch must hide Register' );
	}

	ConnectOptions::set_test_get(
		static function (): array {
			return array(
				'status' => 200,
				'body'   => array(
					'registration'     => true,
					'registration_url' => 'javascript:alert(1)',
				),
			);
		}
	);
	if ( ConnectOptions::for_settings( $settings )->visible_with( true ) ) {
		throw new RuntimeException( 'a non-http registration url must hide Register' );
	}

	ConnectOptions::set_test_get(
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
	$open = ConnectOptions::for_settings( $settings );
	ConnectOptions::set_test_get( null );
	$html = SettingsPage::markup(
		array(),
		'',
		new ConnectStatus( false ),
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start',
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect',
		'',
		$open
	);
	$start = 'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_start';
	if ( false === strpos( $html, 'rs-settings-fp__button--primary' ) || false === strpos( $html, 'formaction="' . $start . '"' ) ) {
		throw new RuntimeException( 'Login must stay a primary Connect submit' );
	}
	if ( false === strpos( $html, 'href="https://studio.example/users/sign_up"' ) || false === strpos( $html, 'target="_blank"' ) ) {
		throw new RuntimeException( 'Register must open the host signup url in a new tab' );
	}
	if ( false === strpos( $html, 'rs-settings-fp__button--outline' ) || false !== strpos( $html, '>Register</button>' ) ) {
		throw new RuntimeException( 'Register must be the secondary link, not a Connect submit' );
	}
	if ( 1 !== substr_count( $html, 'formaction="' . $start . '"' ) ) {
		throw new RuntimeException( 'only Login should post to Connect start' );
	}

	$connected = SettingsPage::markup(
		array(),
		'',
		new ConnectStatus( true ),
		$start,
		'http://localhost:8888/wp-admin/admin-post.php?action=recording_studio_oauth_disconnect',
		'',
		$open
	);
	if ( false !== strpos( $connected, '>Register</a>' ) ) {
		throw new RuntimeException( 'connected settings must hide Register' );
	}
}
