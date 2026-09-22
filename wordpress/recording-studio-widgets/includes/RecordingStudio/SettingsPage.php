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

		$html  = '<div class="wrap rs-settings-fp">';
		$html .= '<h1 class="rs-settings-fp__title">' . esc_html( (string) $config['name'] ) . '</h1>';
		$html .= self::notice_markup( $notice, (bool) $config['oauth_connect'] );
		$html .= '<form method="post" class="rs-settings-fp__card">';
		$html .= $nonce_html;
		if ( $config['oauth_connect'] ) {
			$html .= self::connect_buttons(
				$status->connected,
				$connect_action_url,
				$disconnect_action_url,
				(string) $config['login_button_text'],
				(string) $config['register_button_text']
			);
		}
		if ( $config['api_keys'] ) {
			$settings = PluginSettings::from_storage_array( $stored );
			$api_key  = $settings->advanced_api_key();
			$secret   = $settings->client_secret;
			$html    .= '<details class="rs-settings-fp__advanced">';
			$html    .= '<summary>' . esc_html( 'Advanced' ) . '</summary>';
			$html    .= '<p class="rs-settings-fp__section">' . esc_html( 'Connect via API key' ) . '</p>';
			$html    .= self::field( 'rs_api_key', 'API key', 'text', $api_key, 'The key from your app.' );
			$html    .= self::field( 'rs_client_secret', 'Secret key', 'password', $secret, 'The matching secret. Leave blank to clear it.' );
			$html    .= self::save_and_test();
			$html    .= '</details>';
		}
		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	public static function title(): string {
		return (string) ProductConfig::all()['name'];
	}

	private static function connect_buttons(
		bool $connected,
		string $connect_action_url,
		string $disconnect_action_url,
		string $login_label,
		string $register_label
	): string {
		if ( $connected ) {
			return self::connected_status_banner()
				. '<div class="rs-settings-fp__actions">'
				. self::outline_button( $disconnect_action_url, 'Disconnect' ) . ' '
				. self::outline_button( $connect_action_url, 'Connect again' )
				. '</div>';
		}

		return '<div class="rs-settings-fp__actions">'
			. self::outline_button( $connect_action_url, $login_label ) . ' '
			. self::outline_button( $connect_action_url, $register_label )
			. '</div>';
	}

	private static function outline_button( string $action_url, string $label ): string {
		return '<button type="submit" class="rs-settings-fp__button rs-settings-fp__button--outline" formaction="' . esc_attr( $action_url ) . '">' . esc_html( $label ) . '</button>';
	}

	private static function save_and_test(): string {
		return '<div class="rs-settings-fp__actions">'
			. '<button type="submit" name="rs_save_settings" class="rs-settings-fp__button rs-settings-fp__button--primary">' . esc_html( 'Save settings' ) . '</button> '
			. '<button type="submit" name="rs_test_connection" class="rs-settings-fp__button rs-settings-fp__button--outline">' . esc_html( 'Test connection' ) . '</button>'
			. '</div>';
	}

	private static function field( string $name, string $label, string $type, string $value, string $help ): string {
		$autocomplete = 'password' === $type ? ' autocomplete="new-password"' : ( 'text' === $type ? ' autocomplete="off"' : '' );
		return '<div class="rs-settings-fp__field">'
			. '<label class="rs-settings-fp__label" for="' . esc_attr( $name ) . '">' . esc_html( $label ) . '</label>'
			. '<input class="rs-settings-fp__input" name="' . esc_attr( $name ) . '" id="' . esc_attr( $name ) . '" type="' . esc_attr( $type ) . '" value="' . esc_attr( $value ) . '"' . $autocomplete . ' />'
			. '<p class="rs-settings-fp__help">' . esc_html( $help ) . '</p>'
			. '</div>';
	}

	private static function connected_status_banner(): string {
		return '<div class="notice notice-success rs-settings-fp__alert"><p>' . esc_html( ConnectNotice::message( ConnectNotice::CONNECTED ) ) . '</p></div>';
	}

	private static function notice_markup( string $notice, bool $oauth_connect ): string {
		$entry = ConnectNotice::lookup( $notice );
		if ( null === $entry ) {
			return '';
		}

		if ( ConnectNotice::CONNECTED === $notice && ! $oauth_connect ) {
			return '';
		}

		return '<div class="notice notice-' . esc_attr( $entry['kind'] ) . ' is-dismissible rs-settings-fp__alert"><p>' . esc_html( $entry['message'] ) . '</p></div>';
	}
}
