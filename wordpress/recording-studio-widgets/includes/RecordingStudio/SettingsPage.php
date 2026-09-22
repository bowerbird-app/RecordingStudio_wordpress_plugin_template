<?php

declare(strict_types=1);

namespace RecordingStudio;

final class SettingsPage {
	/**
	 * @param array<string, mixed> $stored
	 */
	public static function markup(
		array $stored,
		string $notice,
		ConnectStatus $status,
		string $connect_action_url,
		string $disconnect_action_url,
		string $nonce_html = ''
	): string {
		$config = ProductConfig::all();

		$html  = '<div class="wrap">';
		$html .= '<h1>' . esc_html( (string) $config['name'] ) . '</h1>';
		$html .= self::notice_markup( $notice, (bool) $config['oauth_connect'] );
		$html .= '<form method="post">';
		$html .= $nonce_html;
		if ( $config['oauth_connect'] ) {
			$html .= self::connect_buttons( $status->connected, $connect_action_url, $disconnect_action_url );
		}
		if ( $config['api_keys'] ) {
			$settings = PluginSettings::from_storage_array( $stored );
			$api_key  = $settings->advanced_api_key();
			$secret   = $settings->client_secret;
			$html    .= '<details>';
			$html    .= '<summary>' . esc_html( 'Advanced' ) . '</summary>';
			$html    .= '<p><strong>' . esc_html( 'Connect via API key' ) . '</strong></p>';
			$html    .= '<table class="form-table" role="presentation">';
			$html    .= self::text_row( 'rs_api_key', 'API key', 'text', $api_key, '' );
			$html    .= self::text_row( 'rs_client_secret', 'Secret key', 'password', $secret, '' );
			$html    .= '</table>';
			$html    .= '</details>';
		}
		$html .= '<p class="submit">';
		$html .= '<button type="submit" name="rs_save_settings" class="button button-primary">' . esc_html( 'Save settings' ) . '</button> ';
		$html .= '<button type="submit" name="rs_test_connection" class="button">' . esc_html( 'Test connection' ) . '</button>';
		$html .= '</p>';
		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	public static function title(): string {
		return (string) ProductConfig::all()['name'];
	}

	private static function connect_buttons( bool $connected, string $connect_action_url, string $disconnect_action_url ): string {
		if ( $connected ) {
			return self::connected_status_banner()
				. '<p class="submit">'
				. '<button type="submit" class="button" formaction="' . esc_attr( $disconnect_action_url ) . '">' . esc_html( 'Disconnect' ) . '</button> '
				. '<button type="submit" class="button" formaction="' . esc_attr( $connect_action_url ) . '">' . esc_html( 'Connect again' ) . '</button>'
				. '</p>';
		}

		return '<p class="submit"><button type="submit" class="button button-primary" formaction="' . esc_attr( $connect_action_url ) . '">' . esc_html( 'Connect to Recording Studio' ) . '</button></p>';
	}

	private static function text_row( string $name, string $label, string $type, string $value, string $placeholder ): string {
		$autocomplete     = 'password' === $type ? ' autocomplete="new-password"' : ( 'text' === $type ? ' autocomplete="off"' : '' );
		$placeholder_attr = '' !== $placeholder ? ' placeholder="' . esc_attr( $placeholder ) . '"' : '';
		return '<tr><th scope="row"><label for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label></th>'
			. '<td><input name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" class="regular-text" value="' . esc_attr( $value ) . '"' . $placeholder_attr . $autocomplete . ' /></td></tr>';
	}

	private static function connected_status_banner(): string {
		return '<div class="notice notice-success"><p>' . esc_html( ConnectNotice::message( ConnectNotice::CONNECTED ) ) . '</p></div>';
	}

	private static function notice_markup( string $notice, bool $oauth_connect ): string {
		$entry = ConnectNotice::lookup( $notice );
		if ( null === $entry ) {
			return '';
		}

		if ( ConnectNotice::CONNECTED === $notice && ! $oauth_connect ) {
			return '';
		}

		return '<div class="notice notice-' . esc_attr( $entry['kind'] ) . ' is-dismissible"><p>' . esc_html( $entry['message'] ) . '</p></div>';
	}
}
