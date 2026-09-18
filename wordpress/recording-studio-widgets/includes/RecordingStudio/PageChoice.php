<?php

declare(strict_types=1);

namespace RecordingStudio;

final class PageChoice {
	/** @var string */
	public string $id;

	/** @var string */
	public string $title;

	public function __construct( string $id, string $title ) {
		$this->id    = $id;
		$this->title = $title;
	}

	/**
	 * @param mixed $body Index JSON.
	 * @return list<self>
	 */
	public static function list_from_index_body( $body ): array {
		if ( ! is_array( $body ) || ! isset( $body['records'] ) ) {
			return array();
		}

		return self::list_from_records( $body['records'] );
	}

	/**
	 * @param mixed $records Index records.
	 * @return list<self>
	 */
	public static function list_from_records( $records ): array {
		if ( ! is_array( $records ) ) {
			return array();
		}

		$pages = array();
		foreach ( $records as $record ) {
			$page = self::from_record( $record );
			if ( null !== $page ) {
				$pages[] = $page;
			}
		}

		return $pages;
	}

	/**
	 * @param mixed $record One records[] item.
	 */
	public static function from_record( $record ): ?self {
		if ( ! is_array( $record ) ) {
			return null;
		}

		$id = isset( $record['id'] ) && is_string( $record['id'] ) ? trim( $record['id'] ) : '';
		if ( '' === $id ) {
			return null;
		}

		$title = isset( $record['title'] ) && is_string( $record['title'] ) ? $record['title'] : '';
		return new self( $id, $title );
	}

	/**
	 * @return array{id: string, title: string}
	 */
	public function to_array(): array {
		return array(
			'id'    => $this->id,
			'title' => $this->title,
		);
	}
}
