<?php
/**
 * Steps wizard
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
<!-- Steps -->
<div class="ubdwp-stepwizard">
	<div class="ubdwp-stepwizard-row setup-panel">
		<div class="ubdwp-stepwizard-step">
			<span id="ubdwp_step_icon_1" class="ubdwp-step-icon is-active" aria-current="step">1</span>
			<p><?php esc_html_e( 'Step', 'users-bulk-delete-with-preview' ); ?> 1</p>
		</div>
		<div class="ubdwp-stepwizard-step">
			<span id="ubdwp_step_icon_2" class="ubdwp-step-icon">2</span>
			<p><?php esc_html_e( 'Step', 'users-bulk-delete-with-preview' ); ?> 2</p>
		</div>
		<div class="ubdwp-stepwizard-step">
			<span id="ubdwp_step_icon_3" class="ubdwp-step-icon">3</span>
			<p><?php esc_html_e( 'Step', 'users-bulk-delete-with-preview' ); ?> 3</p>
		</div>
	</div>
</div>
<!-- Steps -->