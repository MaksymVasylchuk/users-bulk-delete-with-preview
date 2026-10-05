<?php
/**
 * Admin Page
 *
 * @package UsersBulkDeleteWithPreview\Templates
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Templates are included inside a render method, their variables are not global.

if ( ! defined( 'ABSPATH' ) ) {
	// Security check: Ensure the file is not accessed directly.
	echo 'Hi there! I\'m just a plugin, not much I can do when called directly.';
	exit;
}

$title = $title ?? '';
?>
<!-- Loader -->
<div id="page_loader" style="display: none;">
	<div class="loader"></div>
	<p id="ubdwp_loader_text" class="ubdwp-loader-text" aria-live="polite"></p>
</div>
<!-- Loader -->

<!-- Main page -->
<div class="wrap ubdwp-page">
	<h2><?php echo esc_html( $title ); ?></h2>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-1">

			<div id="notices">
			</div>

			<?php require_once 'partials/_step-wizard.php'; ?>

			<?php require_once 'steps/step-1.php'; ?>

			<?php require_once 'steps/step-2.php'; ?>

			<?php require_once 'steps/step-3.php'; ?>

		</div>
	</div>
</div>
<!-- Main page -->