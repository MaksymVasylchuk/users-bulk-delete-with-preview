<?php
/**
 * WooCommerce filters
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
<!-- WooCommerce Filters Form -->
<!-- Products -->
<?php if ( ! empty( $woocommerce_active ) ) : ?>
<tr class="ubdwp-filter-woocommerce" style="display: none;">
	<th scope="row">
		<label for="ubdwp_products"><?php esc_html_e( 'Select products that bought user', 'users-bulk-delete-with-preview' ); ?>:</label>
	</th>
	<td>
		<select id="ubdwp_products" name="products[]" multiple="multiple"></select>
		<input type="hidden" id="ubdwp_search_products_nonce" name="search_products_nonce" value="<?php echo esc_attr( wp_create_nonce( 'ubdwp_search_products' ) ); ?>" />
		<label for="ubdwp_select_all_products" class="ubdwp-checkbox">
			<input type="checkbox" id="ubdwp_select_all_products" name="all_products" value="1">
			<?php esc_html_e( 'Select All', 'users-bulk-delete-with-preview' ); ?>
		</label>
	</td>
</tr>
<?php endif; ?>
<!-- Products -->
<!-- WooCommerce Filters Form -->