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
<?php if ( ! empty( $woocommerce_active ) ) : ?>
<tr class="woocommerce_filters_form" style="display: none;">
	<th scope="row">
		<label for="products"><?php esc_html_e( 'Select products that bought user', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<select id="products" name="products[]" multiple="multiple" class="form-control"></select>
		<input type="hidden" id="search_products_nonce" name="search_products_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ubdwp_search_products' ) ); ?>" />
		<label for="selectAllProducts" class="ubdwp-checkbox">
			<input type="checkbox" id="selectAllProducts" name="all_products" value="1">
			<?php esc_html_e( 'Select All', 'users-bulk-delete-with-preview' ); ?>
		</label>
	</td>
</tr>
<?php endif; ?>
<!-- Products -->
<!-- WooCommerce Filters Form -->