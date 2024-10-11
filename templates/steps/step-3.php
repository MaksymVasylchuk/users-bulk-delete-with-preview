<?php
/**
 * Step 3
 *
 * @package UsersBulkDeleteWithPreview\Templates\Steps
 */

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

            <table id="user_delete_success_table" class="wp-list-table widefat fixed striped">
                <thead>
                <tr>
                    <th>User ID</th>
                    <th>Display name</th>
                    <th>Email</th>
                    <th>Reassign user ID</th>
                </tr>
                </thead>
                <tbody id="user_delete_success_list">
                </tbody>
            </table>


        </div>
    </div>
</div>
<!-- Step 3 -->