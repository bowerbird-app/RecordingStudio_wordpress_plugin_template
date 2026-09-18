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
		$host      = (string) ( $stored['host_base_url'] ?? '' );
		$client_id = (string) ( $stored['client_id'] ?? '' );
		$secret    = (string) ( $stored['client_secret'] ?? '' );
		$token_url = (string) ( $stored['token_url_override'] ?? '' );

		$html  = '<div class="wrap">';
		$html .= '<h1>' . esc_html( self::title() ) . '</h1>';
		$html .= self::notice_markup( $notice );
		$html .= '<form method="post">';
		$html .= $nonce_html;
		$html .= '<table class="form-table" role="presentation">';
		$html .= self::text_row( 'rs_host_base_url', 'Host base URL', 'url', $host, 'http://localhost:3000' );
		$html .= self::text_row( 'rs_client_id', 'OAuth client id', 'text', $client_id, '' );
		$html .= '</table>';
		$html .= self::connect_buttons( $status->connected, $connect_action_url, $disconnect_action_url );
		$html .= '<details>';
		$html .= '<summary>' . esc_html( 'Advanced' ) . '</summary>';
		$html .= '<table class="form-table" role="presentation">';
		$html .= self::text_row( 'rs_client_secret', 'OAuth client secret', 'password', $secret, '' );
		$html .= self::text_row( 'rs_token_url_override', 'Token URL override (optional)', 'url', $token_url, '' );
		$html .= '</table>';
		$html .= '<p class="submit">';
		$html .= '<button type="submit" name="rs_save_settings" class="button button-primary">' . esc_html( 'Save settings' ) . '</button> ';
		$html .= '<button type="submit" name="rs_test_connection" class="button">' . esc_html( 'Test connection' ) . '</button>';
		$html .= '</p>';
		$html .= '</details>';
		$html .= '</form>';
		$html .= '</div>';

		return $html;
	}

	public static function title(): string {
		return 'WordPress Plugin Demo';
	}

	private static function connect_buttons( bool $connected, string $connect_action_url, string $disconnect_action_url ): string {
		if ( $connected ) {
			return '<p>' . esc_html( 'This site is connected.' ) . '</p>'
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

	private static function notice_markup( string $notice ): string {
		$entry = ConnectNotice::lookup( $notice );
		if ( null === $entry ) {
			return '';
		}

		return '<div class="notice notice-' . esc_attr( $entry['kind'] ) . ' is-dismissible"><p>' . esc_html( $entry['message'] ) . '</p></div>';
	}
}
