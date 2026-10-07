<?php
/**
 * Buttons for step 2
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
<p class="submit">
	<!-- Previous Step -->
	<button type="button" class="button button-secondary ubdwp-previous-step"><?php esc_html_e( 'Previous step', 'users-bulk-delete-with-preview' ); ?></button>
	<!-- Previous Step -->
	<!-- Export button -->
	<button type="button" class="button button-secondary export_selected_users ubdwp-export-button"><?php esc_html_e( 'Export Selected Users', 'users-bulk-delete-with-preview' ); ?></button>
	<!-- Export button -->
	<!-- Delete button -->
	<button type="button" class="button button-primary delete_selected_users ubdwp-delete-button"><?php esc_html_e( 'Delete selected users', 'users-bulk-delete-with-preview' ); ?></button>
	<!-- Delete button -->
</p>