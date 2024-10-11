<?php
/**
 * Step 1
 *
 * @package WPUserBulkDeleteWithPreviw\Templates\Steps
 */

?>
<!-- Step 1 -->
<div id="step-1" class="form-step">
	<!-- Search users form -->
	<form action="#" method="post" id="search_users_form">
		<input type="hidden" id="find_users_nonce" name="find_users_nonce" value="<?php echo esc_attr( wp_create_nonce( 'find_users_nonce' ) ); ?>">
		<input type="hidden" name="action" value="search_users_for_delete">
		<table class="form-table">
			<tbody>
			<!-- Filter Type Selector -->
			<?php if ( isset( $types ) && ! empty( $types ) ) : ?>
				<tr>
					<th scope="row">
						<label for="filter_type"><?php esc_html_e( 'Choose filter type', 'users-bulk-delete-with-preview' ); ?>:</label>
					</th>
					<td>
						<div class="form-group">
							<select id="filter_type" name="filter_type" class="form-control">
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

			</tbody>
		</table>
		<!-- Preview Button -->
		<p class="submit">
			<button type="button" class="button button-primary preview_before_remove"><?php esc_html_e( 'Preview', 'users-bulk-delete-with-preview' ); ?></button>
		</p>
	</form>
	<!-- Search users form -->
</div>
<!-- Step 1 -->