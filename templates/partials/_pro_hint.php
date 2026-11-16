<?php
/**
 * Short note about a feature of the Pro add-on, shown where that feature would help
 *
 * @package UsersBulkDeleteWithPreview\Templates\Partials
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Templates are included inside a render method, their variables are not global.

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

use UsersBulkDeleteWithPreview\Facades\UbdwpHelperFacade;

$pro_hint = $pro_hint ?? '';

if ( '' === $pro_hint || ! UbdwpHelperFacade::show_pro_hints() ) {
	return;
}
?>
<p class="ubdwp-pro-hint">
	<span class="dashicons dashicons-star-filled" aria-hidden="true"></span>
	<span><?php echo esc_html( $pro_hint ); ?>
		<a href="<?php echo esc_url( UbdwpHelperFacade::get_pro_url() ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Learn about Pro', 'users-bulk-delete-with-preview' ); ?><span class="screen-reader-text"> <?php esc_html_e( '(opens in a new tab)', 'users-bulk-delete-with-preview' ); ?></span></a>
	</span>
	<a class="ubdwp-pro-hint-hide" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'ubdwp_hide_pro_hints', '1' ), 'ubdwp_hide_pro_hints' ) ); ?>"><?php esc_html_e( 'Hide these tips', 'users-bulk-delete-with-preview' ); ?></a>
</p>
