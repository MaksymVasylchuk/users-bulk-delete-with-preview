<?php
/**
 * Steps wizard
 *
 * @package UsersBulkDeleteWithPreview\Templates\Partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}
?>
<!-- Steps -->
<div class="stepwizard">
	<div class="stepwizard-row setup-panel">
		<div class="stepwizard-step">
			<a href="#step-1" id="step_icon_1" type="button" class="btn btn-primary btn-circle step_icon">1</a>
			<p><?php esc_html_e( 'Step', 'users-bulk-delete-with-preview' ); ?> 1</p>
		</div>
		<div class="stepwizard-step">
			<a href="#step-2" type="button" id="step_icon_2" class="btn btn-default btn-circle step_icon" disabled="disabled">2</a>
			<p><?php esc_html_e( 'Step', 'users-bulk-delete-with-preview' ); ?> 2</p>
		</div>
		<div class="stepwizard-step">
			<a href="#step-3" type="button" id="step_icon_3" class="btn btn-default btn-circle step_icon" disabled="disabled">3</a>
			<p><?php esc_html_e( 'Step', 'users-bulk-delete-with-preview' ); ?> 3</p>
		</div>
	</div>
</div>
<!-- Steps -->