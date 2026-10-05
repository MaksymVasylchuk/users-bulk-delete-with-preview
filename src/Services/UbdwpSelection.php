<?php
/**
 * Selection of preview users
 *
 * @package     UsersBulkDeleteWithPreview\Services
 */

namespace UsersBulkDeleteWithPreview\Services;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Turns the selection sent by the admin page into a list of user IDs and content reassignments.
 *
 * The browser only knows the current table page, so a selection is either "all users of the preview
 * except some" or "these users", plus a default reassign value and per-user overrides.
 */
class UbdwpSelection {
	/**
	 * Reassign value that removes the user's content.
	 */
	public const REMOVE_CONTENT = 'remove_all_related_content';

	/**
	 * Resolve a selection against the users of a preview.
	 *
	 * Only users of the preview can be selected, whatever the request contains.
	 *
	 * @param array<int>           $preview_ids User IDs of the preview.
	 * @param array<string, mixed> $selection   Decoded selection: all, ids, except, default, reassign.
	 *
	 * @return array{ids: array<int>, default: string, reassign: array<int, string>} Selected users.
	 */
	public static function resolve( array $preview_ids, array $selection ): array {
		$in_preview = array_flip( $preview_ids );

		if ( ! empty( $selection['all'] ) ) {
			$except = array_flip( self::to_ids( $selection['except'] ?? array() ) );
			$ids    = array_values( array_filter( $preview_ids, static fn( int $id ): bool => ! isset( $except[ $id ] ) ) );
		} else {
			$ids = array_values( array_filter( self::to_ids( $selection['ids'] ?? array() ), static fn( int $id ): bool => isset( $in_preview[ $id ] ) ) );
			sort( $ids );
		}

		$selected = array_flip( $ids );
		$reassign = array();

		foreach ( (array) ( $selection['reassign'] ?? array() ) as $user_id => $value ) {
			$user_id = absint( $user_id );

			if ( isset( $selected[ $user_id ] ) ) {
				$reassign[ $user_id ] = self::sanitize_reassign( $value );
			}
		}

		return array(
			'ids'      => $ids,
			'default'  => self::sanitize_reassign( $selection['default'] ?? '' ),
			'reassign' => $reassign,
		);
	}

	/**
	 * Decode the selection JSON sent by the admin page.
	 *
	 * @param mixed $json Raw value.
	 *
	 * @return array<string, mixed> Selection, empty when the value is not valid JSON.
	 */
	public static function decode( $json ): array {
		if ( ! is_string( $json ) ) {
			return array();
		}

		$selection = json_decode( $json, true );

		return is_array( $selection ) ? $selection : array();
	}

	/**
	 * Normalize a reassign value: empty, "remove all related content" or a user ID.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string Reassign value.
	 */
	public static function sanitize_reassign( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}

		$value = (string) $value;

		if ( self::REMOVE_CONTENT === $value ) {
			return $value;
		}

		return ctype_digit( $value ) && absint( $value ) > 0 ? (string) absint( $value ) : '';
	}

	/**
	 * Convert a list of raw values to unique positive user IDs.
	 *
	 * @param mixed $values Raw values.
	 *
	 * @return array<int> User IDs.
	 */
	private static function to_ids( $values ): array {
		$ids = array();

		foreach ( (array) $values as $value ) {
			if ( is_scalar( $value ) && absint( $value ) > 0 ) {
				$ids[ absint( $value ) ] = true;
			}
		}

		return array_keys( $ids );
	}
}
