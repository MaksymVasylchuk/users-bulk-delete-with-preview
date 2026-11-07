<?php
/**
 * Find users filter elements
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
<!-- Find Users Form -->
<!-- Group: Account -->
<tr class="ubdwp-filter-find ubdwp-filter-group" style="display: none;">
	<th colspan="2" scope="colgroup"><h3 class="ubdwp-filter-group-title"><?php esc_html_e( 'Account', 'users-bulk-delete-with-preview' ); ?></h3></th>
</tr>
<!-- User Role -->
<?php if ( isset( $roles ) && ! empty( $roles ) ) : ?>
	<tr class="ubdwp-filter-find" style="display: none;">
		<th scope="row">
			<label for="ubdwp_user_role"><?php esc_html_e( 'User Role', 'users-bulk-delete-with-preview' ); ?>:</label>
		</th>
		<td>
			<select id="ubdwp_user_role" name="user_role[]" multiple="multiple">
				<?php foreach ( $roles as $role_key => $role ) : ?>
					<option value="<?php echo esc_attr( $role_key ); ?>"><?php echo esc_html( $role['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
	</tr>
<?php endif; ?>
<!-- User Role -->
<!-- User Email -->
<tr class="ubdwp-filter-find" style="display: none;">
	<th scope="row">
		<label for="ubdwp_user_email"><?php esc_html_e( 'User Email', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<div class="ubdwp-field-row">
		<select name="user_email_equal" id="ubdwp_user_email_equal" class="ubdwp-operator" data-ubdwp-ignore="1">
			<option value="equal_to_str"><?php esc_html_e( 'Equal to (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="notequal_to_str"><?php esc_html_e( 'Not equal to (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="like_str"><?php esc_html_e( 'Like (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="notlike_str"><?php esc_html_e( 'Not like (string)', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="endswith_str"><?php esc_html_e( 'Ends with (for example @example.com)', 'users-bulk-delete-with-preview' ); ?></option>
		</select>
		<input type="text" id="ubdwp_user_email" name="user_email" class="regular-text" placeholder="<?php esc_attr_e( 'Enter user email...', 'users-bulk-delete-with-preview' ); ?>">
		</div>
	</td>
</tr>
<!-- User Email -->
<!-- User Registration Date -->
<tr class="ubdwp-filter-find" style="display: none;">
	<th scope="row">
		<label for="ubdwp_registration_date"><?php esc_html_e( 'User Registration Date', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<div class="ubdwp-field-row">
		<select name="registration_date_compare" id="ubdwp_registration_date_compare" class="ubdwp-operator" data-ubdwp-ignore="1" aria-label="<?php esc_attr_e( 'Registration date comparison', 'users-bulk-delete-with-preview' ); ?>">
			<option value="after"><?php esc_html_e( 'On or after', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="before"><?php esc_html_e( 'On or before', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="on"><?php esc_html_e( 'On', 'users-bulk-delete-with-preview' ); ?></option>
			<option value="between"><?php esc_html_e( 'Between', 'users-bulk-delete-with-preview' ); ?></option>
		</select>
		<input type="date" id="ubdwp_registration_date" name="registration_date" class="regular-text ubdwp-short" autocomplete="off">
		<span id="ubdwp_registration_date_to_wrap" class="ubdwp-inline-group" style="display: none;">
			<label for="ubdwp_registration_date_to"><?php esc_html_e( 'and', 'users-bulk-delete-with-preview' ); ?></label>
			<input type="date" id="ubdwp_registration_date_to" name="registration_date_to" class="regular-text ubdwp-short" autocomplete="off">
		</span>
		</div>
	</td>
</tr>
<!-- User Registration Date -->
<?php
/**
 * Fires at the end of the "Account" group of "Find users according to certain criteria", to add rows (<tr>) of
 * other plugins to the group. Give the rows the class "ubdwp-filter-find" and style="display: none;".
 *
 * @since 2.5.0
 */
do_action( 'ubdwp_find_fields_account' );
?>
<!-- Group: Activity -->
<tr class="ubdwp-filter-find ubdwp-filter-group" style="display: none;">
	<th colspan="2" scope="colgroup"><h3 class="ubdwp-filter-group-title"><?php esc_html_e( 'Activity', 'users-bulk-delete-with-preview' ); ?></h3></th>
</tr>
<!-- Without Content -->
<tr class="ubdwp-filter-find" style="display: none;">
	<th scope="row">
		<?php esc_html_e( 'Content', 'users-bulk-delete-with-preview' ); ?>:
	</th>
	<td>
		<label for="ubdwp_without_content" class="ubdwp-checkbox">
			<input type="checkbox" id="ubdwp_without_content" name="without_content" value="1">
			<?php esc_html_e( 'Only users without posts or comments on this site', 'users-bulk-delete-with-preview' ); ?>
		</label>
		<?php if ( ! empty( $woocommerce_active ) ) : ?>
			<label for="ubdwp_without_wc_orders" class="ubdwp-checkbox">
				<input type="checkbox" id="ubdwp_without_wc_orders" name="without_wc_orders" value="1">
				<?php esc_html_e( 'Only users without WooCommerce orders', 'users-bulk-delete-with-preview' ); ?>
			</label>
		<?php endif; ?>
	</td>
</tr>
<!-- Without Content -->
<?php
/**
 * Fires at the end of the "Activity" group of "Find users according to certain criteria", to add rows (<tr>) of
 * other plugins to the group. Give the rows the class "ubdwp-filter-find" and style="display: none;".
 *
 * @since 2.5.0
 */
do_action( 'ubdwp_find_fields_activity' );
?>
<?php if ( has_action( 'ubdwp_find_fields_spam' ) ) : ?>
<!-- Group: Spam signs -->
<tr class="ubdwp-filter-find ubdwp-filter-group" style="display: none;">
	<th colspan="2" scope="colgroup"><h3 class="ubdwp-filter-group-title"><?php esc_html_e( 'Spam signs', 'users-bulk-delete-with-preview' ); ?></h3></th>
</tr>
<?php
/**
 * Fires at the end of the "Spam signs" group of "Find users according to certain criteria", to add rows (<tr>) of
 * other plugins to the group. Give the rows the class "ubdwp-filter-find" and style="display: none;".
 *
 * @since 2.5.0
 */
do_action( 'ubdwp_find_fields_spam' );
?>
<?php endif; ?>
<?php if ( has_action( 'ubdwp_find_fields_fraud' ) ) : ?>
<!-- Group: Fraud signals -->
<tr class="ubdwp-filter-find ubdwp-filter-group" style="display: none;">
	<th colspan="2" scope="colgroup"><h3 class="ubdwp-filter-group-title"><?php esc_html_e( 'Fraud signals', 'users-bulk-delete-with-preview' ); ?></h3></th>
</tr>
<?php
/**
 * Fires at the end of the "Fraud signals" group of "Find users according to certain criteria", to add rows (<tr>) of
 * other plugins to the group. Give the rows the class "ubdwp-filter-find" and style="display: none;". The group is
 * only shown when something is hooked.
 *
 * @since 2.5.0
 */
do_action( 'ubdwp_find_fields_fraud' );
?>
<?php endif; ?>
<!-- Group: Advanced -->
<tr class="ubdwp-filter-find ubdwp-filter-group" style="display: none;">
	<th colspan="2" scope="colgroup"><h3 class="ubdwp-filter-group-title"><?php esc_html_e( 'Advanced', 'users-bulk-delete-with-preview' ); ?></h3></th>
</tr>
<!-- User Meta -->
<tr class="ubdwp-filter-find" style="display: none;">
	<th scope="row">
		<label for="ubdwp_user_meta"><?php esc_html_e( 'User Meta', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<input type="hidden" id="ubdwp_search_user_meta_nonce" name="search_user_meta_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ubdwp_search_usermeta' ) ); ?>" />
		<div class="ubdwp-field-row">
		<select class="regular-text" name="user_meta" id="ubdwp_user_meta"></select>
		<select name="user_meta_equal" id="ubdwp_user_meta_equal" data-ubdwp-ignore="1" class="ubdwp-operator">
            <option value="meta_not_exists"><?php esc_html_e( 'Meta does not exist', 'users-bulk-delete-with-preview' ); ?></option>
            <option value="meta_is_empty"><?php esc_html_e( 'Meta is empty or missing', 'users-bulk-delete-with-preview' ); ?></option>
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
		<input type="text" id="ubdwp_user_meta_value" name="user_meta_value" class="regular-text ubdwp-short" placeholder="<?php esc_attr_e( 'Enter user meta value', 'users-bulk-delete-with-preview' ); ?>">
		</div>
		<p class="description"><?php esc_html_e( 'Tip: WordPress stores profile fields such as first_name for every user, even when they are blank. To find users who left a field empty, choose "Meta is empty or missing".', 'users-bulk-delete-with-preview' ); ?></p>
	</td>
</tr>
<!-- User Meta -->
<?php
/**
 * Fires at the end of the "Advanced" group of "Find users according to certain criteria", to add rows (<tr>) of
 * other plugins to the group. Give the rows the class "ubdwp-filter-find" and style="display: none;".
 *
 * @since 2.5.0
 */
do_action( 'ubdwp_find_fields_advanced' );
?>
<!-- Find Users Form -->
