<?php
/**
 * Admin Page
 *
 * @package UsersBulkDeleteWithPreview\Templates
 */

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
</div>
<!-- Loader -->

<!-- Main page -->
<div class="wrap">
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