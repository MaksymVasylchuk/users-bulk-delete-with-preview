<?php
/**
 * Existing users filter field(s)
 *
 * @package UsersBulkDeleteWithPreview\Templates\Partials
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Templates are included inside a render method, their variables are not global.

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}
?>
<!-- Existing Users Form -->
<tr class="ubdwp-filter-existing" style="display: none;">
	<input type="hidden" id="ubdwp_search_user_existing_nonce" name="search_user_existing_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ubdwp_search_users' ) ); ?>" />
	<th scope="row">
		<label for="ubdwp_user_search"><?php esc_html_e( 'Select existing users', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<select id="ubdwp_user_search" name="user_search[]" multiple="multiple"></select>
		<span class="invalid-feedback"></span>
		<label for="ubdwp_select_all_users" class="ubdwp-checkbox">
			<input type="checkbox" id="ubdwp_select_all_users" name="all_users" value="1">
			<?php esc_html_e( 'All users of this site', 'users-bulk-delete-with-preview' ); ?>
		</label>
	</td>
</tr>
<!-- Existing Users Form -->