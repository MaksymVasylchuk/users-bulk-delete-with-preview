<?php
/**
 * WooCommerce filters
 *
 * @package UsersBulkDeleteWithPreview\Templates\Partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}
?>
<!-- WooCommerce Filters Form -->
<!-- Products -->
<?php if ( isset( $products ) && ! empty( $products ) ) : ?>
<tr class="woocommerce_filters_form" style="display: none;">
	<th scope="row">
		<label for="products"><?php esc_html_e( 'Select products that bought user', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<select id="products" name="products[]" multiple="multiple" class="form-control">
			<?php foreach ( $products as $product_key => $product ) : ?>
				<option value="<?php echo esc_attr( $product->get_id() ); ?>"><?php echo esc_html( $product->get_name() ); ?></option>
			<?php endforeach; ?>
		</select>
		<br>
		<label for="selectAllProducts">
			<input type="checkbox" id="selectAllProducts" name="selectAllProducts">
			<?php esc_html_e( 'Select All', 'users-bulk-delete-with-preview' ); ?>
		</label>
	</td>
</tr>
<?php endif; ?>
<!-- Products -->
<!-- WooCommerce Filters Form -->