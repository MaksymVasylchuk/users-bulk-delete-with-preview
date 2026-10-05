<?php
/**
 * Preview session
 *
 * @package     UsersBulkDeleteWithPreview\Services
 */

namespace UsersBulkDeleteWithPreview\Services;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Stores the IDs of all users found by a preview, so the table can be paged on the server
 * and large selections ("all matching users") can be sent as a short request.
 *
 * A session belongs to the user who created it and to the current site.
 */
class UbdwpPreviewSession {
	/**
	 * How long a preview stays usable.
	 */
	public const TTL = 2 * HOUR_IN_SECONDS;

	/**
	 * Transient name prefix.
	 */
	private const PREFIX = 'ubdwp_preview_';

	/**
	 * User option that remembers the latest preview of a user on a site.
	 */
	private const LATEST_OPTION = 'ubdwp_latest_preview';

	/**
	 * Store a new preview and return its token.
	 *
	 * The previous preview of the same user is removed, so only one large ID list is kept per user.
	 *
	 * @param int        $user_id  Owner.
	 * @param array<int> $user_ids IDs of the users found.
	 *
	 * @return string Token.
	 */
	public static function create( int $user_id, array $user_ids ): string {
		$previous = get_user_option( self::LATEST_OPTION, $user_id );

		if ( is_string( $previous ) && self::is_valid_token( $previous ) ) {
			delete_transient( self::PREFIX . $previous );
		}

		$token = wp_generate_password( 20, false, false );

		set_transient(
			self::PREFIX . $token,
			array(
				'user_id' => $user_id,
				'blog_id' => get_current_blog_id(),
				'ids'     => implode( ',', array_map( 'absint', $user_ids ) ),
			),
			self::TTL
		);

		update_user_option( $user_id, self::LATEST_OPTION, $token );

		return $token;
	}

	/**
	 * Get the user IDs of a preview owned by the user on the current site.
	 *
	 * @param string $token   Token.
	 * @param int    $user_id User requesting the preview.
	 *
	 * @return array<int>|null User IDs, or null when the preview does not exist, expired or belongs to someone else.
	 */
	public static function get_user_ids( string $token, int $user_id ): ?array {
		if ( ! self::is_valid_token( $token ) ) {
			return null;
		}

		$session = get_transient( self::PREFIX . $token );

		if (
			! is_array( $session ) ||
			(int) ( $session['user_id'] ?? 0 ) !== $user_id ||
			(int) ( $session['blog_id'] ?? 0 ) !== get_current_blog_id() ||
			! is_string( $session['ids'] ?? null )
		) {
			return null;
		}

		return '' === $session['ids'] ? array() : array_map( 'intval', explode( ',', $session['ids'] ) );
	}

	/**
	 * Remove a preview.
	 *
	 * @param string $token Token.
	 *
	 * @return void
	 */
	public static function delete( string $token ): void {
		if ( self::is_valid_token( $token ) ) {
			delete_transient( self::PREFIX . $token );
		}
	}

	/**
	 * Check the token format before it is used in an option name.
	 *
	 * @param string $token Token.
	 *
	 * @return bool True for a well-formed token.
	 */
	private static function is_valid_token( string $token ): bool {
		return (bool) preg_match( '/^[A-Za-z0-9]{20}$/', $token );
	}
}
