<?php
/**
 * Fins user filters elements
 *
 * @package UsersBulkDeleteWithPreview\Templates\Partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}
?>
<!-- Find Users Form -->
<!-- User Role -->
<?php if ( isset( $roles ) && ! empty( $roles ) ) : ?>
	<tr class="find_users_form" style="display: none;">
		<th scope="row">
			<label for="user_role"><?php esc_html_e( 'User Role', 'users-bulk-delete-with-preview' ); ?>:</label>
		</th>
		<td>
			<select id="user_role" name="user_role[]" multiple="multiple" class="form-control">
				<?php foreach ( $roles as $role_key => $role ) : ?>
					<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
<?php endif; ?>
<!-- User Role -->
<!-- User Email -->
<tr class="find_users_form" style="display: none;">
	<th scope="row">
		<label for="user_email"><?php esc_html_e( 'User Email', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<select name="user_email_equal" id="user_email_equal">
			<option value="equal_to_str"><?php esc_html_e( 'Equal to (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="notequal_to_str"><?php esc_html_e( 'Not equal to (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="like_str"><?php esc_html_e( 'Like (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="notlike_str"><?php esc_html_e( 'Not like (string)', 'users-bulk-delete-with-preview' ); ?></option>
		</select>
		<input type="email" id="user_email" name="user_email" class="regular-text" placeholder="<?php esc_attr_e( 'Enter user email...', 'users-bulk-delete-with-preview' ); ?>">
	</td>
</tr>
<!-- User Email -->
<!-- User Registration Date -->
<tr class="find_users_form" style="display: none;">
	<th scope="row">
		<label for="registration_date"><?php esc_html_e( 'User Registration Date', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<input type="text" id="registration_date" name="registration_date" class="regular-text">
	</td>
</tr>
<!-- User Registration Date -->
<!-- User Meta -->
<tr class="find_users_form" style="display: none;">
	<th scope="row">
		<label for="user_meta"><?php esc_html_e( 'User Meta', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<input type="hidden" id="search_user_meta_nonce" name="search_user_meta_nonce" value="<?php echo esc_attr( wp_create_nonce( 'search_user_meta_nonce' ) ); ?>" />
		<select class="regular-text" name="user_meta" id="user_meta"></select>
		<select name="user_meta_equal" id="user_meta_equal">
			<option value="equal_to_str"><?php esc_html_e( 'Equal to (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="notequal_to_str"><?php esc_html_e( 'Not equal to (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="like_str"><?php esc_html_e( 'Like (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="notlike_str"><?php esc_html_e( 'Not like (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="equal_to_date"><?php esc_html_e( 'Equal to (date)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="notequal_to_date"><?php esc_html_e( 'Not equal to (date)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="lessthen_date"><?php esc_html_e( 'Less than (date)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="lessthenequal_date"><?php esc_html_e( 'Less than or equal to (date)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="greaterthen_date"><?php esc_html_e( 'Greater than (date)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="greaterthenequal_date"><?php esc_html_e( 'Greater than or equal to (date)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="equal_to_number"><?php esc_html_e( 'Equal to (number)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="notequal_to_number"><?php esc_html_e( 'Not equal to (number)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="lessthen_number"><?php esc_html_e( 'Less than (number)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="lessthenequal_number"><?php esc_html_e( 'Less than or equal to (number)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="greaterthen_number"><?php esc_html_e( 'Greater than (number)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="greaterthenequal_number"><?php esc_html_e( 'Greater than or equal to (number)', 'users-bulk-delete-with-preview' ); ?></option>
		</select>
		<input type="text" id="user_meta_value" name="user_meta_value" class="regular-text" placeholder="<?php esc_html_e( 'Enter user meta value', 'users-bulk-delete-with-preview' ); ?>">
	</td>
</tr>
<!-- User Meta -->
<!-- Find Users Form -->