<?php
/**
 * Helper facade
 *
 * @package     UsersBulkDeleteWithPreview\Facades
 */

namespace UsersBulkDeleteWithPreview\Facades;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Abstract\UbdwpAbstractFacade;

/**
 * Facade class for the UbdwpHelper functionality.
 * Extends the abstract class UbdwpAbstractFacade.
 */
class UbdwpHelperFacade extends UbdwpAbstractFacade {

	/**
	 * Get the class name that this facade is representing.
	 *
	 * @return string Fully qualified class name of the helper.
	 */
	protected static function get_class_name(): string {
		return 'UsersBulkDeleteWithPreview\Helpers\UbdwpHelper';
	}
}