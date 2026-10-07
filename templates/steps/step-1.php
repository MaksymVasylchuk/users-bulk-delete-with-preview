<?php
/**
 * Step 1
 *
 * @package UsersBulkDeleteWithPreview\Templates\Steps
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Templates are included inside a render method, their variables are not global.

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}
?>
<!-- Step 1 -->
<div id="ubdwp_step_1" class="ubdwp-form-step">
	<!-- Search users form -->
	<form action="#" method="post" id="ubdwp_search_users_form">
		<input type="hidden" id="ubdwp_find_users_nonce" name="find_users_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ubdwp_find_users' ) ); ?>">
		<input type="hidden" name="action" value="ubdwp_search_users_for_delete">
		<table class="form-table">
			<tbody>
			<!-- Filter Type Selector -->
			<?php if ( isset( $types ) && ! empty( $types ) ) : ?>
				<tr>
					<th scope="row">
						<label for="ubdwp_filter_type"><?php esc_html_e( 'Choose filter type', 'users-bulk-delete-with-preview' ); ?>:</label>
					</th>
					<td>
						<div class="form-group">
							<select id="ubdwp_filter_type" name="filter_type">
								<?php foreach ( $types as $type_key => $type ) : ?>
									<option value="<?php echo esc_attr( $type_key ); ?>"><?php echo esc_html( $type ); ?></option>
								<?php endforeach; ?>
							</select>
						</div>
					</td>
				</tr>
			<?php endif; ?>
			<!-- Filter Type Selector -->

			<?php require_once __DIR__ . '/../partials/_existing_user_form.php'; ?>

			<?php require_once __DIR__ . '/../partials/_find_user_form.php'; ?>

			<?php require_once __DIR__ . '/../partials/_woocommerce_filters_form.php'; ?>

			<?php
			/**
			 * Fires after the filter fields of the Bulk Users Delete page, inside the form table.
			 *
			 * Print table rows (<tr>) with your own fields; they are sent with the preview request.
			 * Give a row the class of a filter type ("ubdwp-filter-existing", "ubdwp-filter-find" or
			 * "ubdwp-filter-woocommerce") and style="display: none;" to show it only for that type, and narrow the result
			 * with the "ubdwp_found_user_ids" filter.
			 *
			 * @since 2.4.0
			 */
			do_action( 'ubdwp_filter_form_fields' );
			?>

			</tbody>
		</table>
		<!-- Preview Button -->
		<p class="submit">
			<button type="button" class="button button-primary ubdwp-preview-button"><?php esc_html_e( 'Preview', 'users-bulk-delete-with-preview' ); ?></button>
		</p>
	</form>
	<!-- Search users form -->
</div>
<!-- Step 1 -->