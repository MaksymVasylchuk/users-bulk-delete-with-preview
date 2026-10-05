<?php
/**
 * Users that were not deleted, with the reason
 *
 * @package UsersBulkDeleteWithPreview\Templates\Partials
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Templates are included inside a render method, their variables are not global.

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

$failed_users = $failed_users ?? array();
?>
<?php foreach ( $failed_users as $failed_user ) : ?>
	<tr>
		<td><?php echo esc_html( $failed_user['user_id'] ); ?></td>
		<td><?php echo esc_html( $failed_user['login'] ); ?></td>
		<td><?php echo esc_html( $failed_user['email'] ); ?></td>
		<td><?php echo esc_html( $failed_user['message'] ); ?></td>
	</tr>
<?php endforeach; ?>
