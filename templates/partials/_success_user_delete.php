<?php
/**
 * Success user delete table
 *
 * @package UsersBulkDeleteWithPreview\Templates\Partials
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Templates are included inside a render method, their variables are not global.

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

$user_delete_count = $user_delete_count ?? 0;
$deleted_users     = $deleted_users ?? array(); // Array with information about deleted users.
?>

<!-- Success deletion -->
<?php if ( $user_delete_count >= 1 && ! empty( $deleted_users ) ) : ?>
		<?php foreach ( $deleted_users as $user ) : ?>
            <tr>
                <td><?php echo esc_html($user['user_id']); ?></td>
                <td><?php echo esc_html($user['display_name']); ?></td>
                <td><?php echo esc_html($user['email']); ?></td>
                <td><?php echo esc_html( \UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade::get_content_action_label( $user['reassign'] ) ); ?></td>
            </tr>
		<?php endforeach; ?>
<?php endif; ?>
<!-- Success deletion -->