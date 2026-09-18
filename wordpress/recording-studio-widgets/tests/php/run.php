<?php
/**
 * Runs plugin PHP unit tests without WordPress bootstrap.
 *
 * @package RecordingStudio
 */

declare(strict_types=1);

$plugin_root = dirname( __DIR__, 2 );

require $plugin_root . '/tests/php/wp-stubs.php';

spl_autoload_register(
	static function ( string $class_name ) use ( $plugin_root ): void {
		$prefix = 'RecordingStudio\\';
		if ( strncmp( $class_name, $prefix, strlen( $prefix ) ) !== 0 ) {
			return;
		}

		$relative = substr( $class_name, strlen( $prefix ) );
		$file     = $plugin_root . '/includes/RecordingStudio/' . str_replace( '\\', '/', $relative ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

foreach (
	array(
		'BlockStylesheetTest.php',
		'BrowserPayloadTest.php',
		'PluginSettingsTest.php',
		'StudioClientTest.php',
		'ConnectTest.php',
		'PageListTest.php',
	) as $file
) {
	require $plugin_root . '/tests/php/' . $file;
}

$failures = 0;

foreach ( get_defined_functions()['user'] as $function ) {
	if ( 0 !== strpos( $function, 'test_' ) ) {
		continue;
	}

	try {
		$function();
		echo "{$function} ok\n";
	} catch ( Throwable $error ) {
		++$failures;
		fwrite( STDERR, "{$function} FAILED: {$error->getMessage()}\n" );
	}
}

if ( $failures > 0 ) {
	fwrite( STDERR, "{$failures} test(s) failed\n" );
	exit( 1 );
}

echo "all php tests ok\n";
