<?php
/**
 * Steps wizard
 *
 * @package WPUserBulkDeleteWithPreviw\Templates\Partials
 */

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
                <td><?php echo esc_html($user['reassign']); ?></td>
            </tr>
		<?php endforeach; ?>
<?php endif; ?>
<!-- Success deletion -->