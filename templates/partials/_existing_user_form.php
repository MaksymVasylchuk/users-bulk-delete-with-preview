<?php
/**
 * Existing users filter field(s)
 *
 * @package UsersBulkDeleteWithPreview\Templates\Partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}
?>
<!-- Existing Users Form -->
<tr class="select_existing_form" style="display: none;">
	<input type="hidden" id="search_user_existing_nonce" name="search_user_existing_nonce" value="<?php echo esc_attr( wp_create_nonce( 'search_user_existing_nonce' ) ); ?>" />
	<th scope="row">
		<label for="user_search"><?php esc_html_e( 'Select existing users', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<select id="user_search" name="user_search[]" multiple="multiple" class="form-control"></select>
		<span class="invalid-feedback"></span>
		<br>
		<label for="use_regexp">
			<input type="checkbox" id="selectAllUsers" name="selectAll">
			<?php esc_html_e( 'Select All', 'users-bulk-delete-with-preview' ); ?>
		</label>
	</td>
</tr>
<!-- Existing Users Form -->