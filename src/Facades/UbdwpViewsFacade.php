<?php
/**
 * Views facade
 *
 * @package     UsersBulkDeleteWithPreview\Facades
 */

namespace UsersBulkDeleteWithPreview\Facades;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use UsersBulkDeleteWithPreview\Abstract\UbdwpAbstractFacade;

/**
 * Facade class for the UbdwpViews functionality.
 * Extends the abstract class UbdwpAbstractFacade.
 */
class UbdwpViewsFacade extends UbdwpAbstractFacade {

	/**
	 * Get the class name that this facade is representing.
	 *
	 * @return string Fully qualified class name of the views utility.
	 */
	protected static function get_class_name(): string {
		return 'UsersBulkDeleteWithPreview\Utils\UbdwpViews';
	}
}
