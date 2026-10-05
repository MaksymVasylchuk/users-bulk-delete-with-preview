<?php
/**
 * Step 3
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
<!-- Step 3 -->
<div id="step-3" class="form-step" style="display: none;">
    <div class="content">
        <div id="user_delete_message">
            <!-- Success deletion -->
            <p class="success_heading" id="user_delete_success_heading">
            </p>
            <p id="ubdwp_results_note" class="ubdwp-results-note" style="display: none;"></p>

            <table id="user_delete_success_table" class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th><?php esc_html_e( 'User ID', 'users-bulk-delete-with-preview' ); ?></th>
                    <th><?php esc_html_e( 'Display name', 'users-bulk-delete-with-preview' ); ?></th>
                    <th><?php esc_html_e( 'Email', 'users-bulk-delete-with-preview' ); ?></th>
                    <th><?php esc_html_e( 'Related content', 'users-bulk-delete-with-preview' ); ?></th>
                </tr>
                </thead>
                <tbody id="user_delete_success_list">
                </tbody>
            </table>

            <div id="user_delete_failed" style="display: none;">
                <h3><?php esc_html_e( 'Users that were not deleted', 'users-bulk-delete-with-preview' ); ?></h3>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                    <tr>
                        <th><?php esc_html_e( 'User ID', 'users-bulk-delete-with-preview' ); ?></th>
                        <th><?php esc_html_e( 'Username', 'users-bulk-delete-with-preview' ); ?></th>
                        <th><?php esc_html_e( 'Email', 'users-bulk-delete-with-preview' ); ?></th>
                        <th><?php esc_html_e( 'Reason', 'users-bulk-delete-with-preview' ); ?></th>
                    </tr>
                    </thead>
                    <tbody id="user_delete_failed_list">
                    </tbody>
                </table>
            </div>


        </div>
    </div>
</div>
<!-- Step 3 -->