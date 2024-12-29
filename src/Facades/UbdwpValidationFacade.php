<?php
/**
 * Validation facade
 *
 * @package     UsersBulkDeleteWithPreview\Facades
 */

namespace UsersBulkDeleteWithPreview\Facades;

// Exit if accessed directly.
defined('ABSPATH') || exit;

use UsersBulkDeleteWithPreview\Abstract\UbdwpAbstractFacade;

/**
 * Facade class for the UbdwpValidation functionality.
 * Extends the abstract class UbdwpAbstractFacade.
 */
class UbdwpValidationFacade extends UbdwpAbstractFacade {

	/**
	 * Get the class name that this facade is representing.
	 *
	 * @return string Fully qualified class name of the validation utility.
	 */
	protected static function get_class_name(): string {
		return 'UsersBulkDeleteWithPreview\Utils\UbdwpValidation';
	}
}