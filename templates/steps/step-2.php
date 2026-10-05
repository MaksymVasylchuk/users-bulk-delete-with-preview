<?php
/**
 * Step 2
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
<!-- Step 2 -->
<div id="step-2" class="form-step" style="display: none;">

    <div id="deleteProgressBar" style="display: none;">
        <div class="progress">
            <div id="progressBarInner" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div id="deletedCount" class="ubdwp-progress-count" aria-live="polite">0 / 0 (0%)</div>
        <p id="ubdwp_background_note" class="ubdwp-progress-note" style="display: none;"></p>
        <p class="ubdwp-progress-actions">
            <button type="button" id="ubdwp_cancel_job" class="button button-secondary"><?php esc_html_e( 'Stop deletion', 'users-bulk-delete-with-preview' ); ?></button>
        </p>
    </div>

	<?php require __DIR__ . '/../partials/_step-2-buttons.php'; ?>

	<div class="content">
		<?php require_once __DIR__ . '/../partials/_users_table.php'; ?>
	</div>

	<?php require __DIR__ . '/../partials/_step-2-buttons.php'; ?>

	<?php require_once __DIR__ . '/../partials/_confirmation-popup.php'; ?>
</div>
<!-- Step 2 -->