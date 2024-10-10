<?php
/**
 * Step 2
 *
 * @package WPUserBulkDeleteWithPreviw\Templates\Steps
 */

?>
<!-- Step 2 -->
<div id="step-2" class="form-step" style="display: none;">

    <div id="deleteProgressBar" style="display: none;">
        <div class="progress">
            <div id="progressBarInner" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div id="deletedCount" class="mt-2" style="text-align: center;">0 / 0 (0%)</div>
    </div>

	<?php require __DIR__ . '/../partials/_step-2-buttons.php'; ?>

	<div class="content">
		<?php require_once __DIR__ . '/../partials/_users_table.php'; ?>
	</div>

	<?php require __DIR__ . '/../partials/_step-2-buttons.php'; ?>

	<?php require_once __DIR__ . '/../partials/_confirmation-popup.php'; ?>
</div>
<!-- Step 2 -->