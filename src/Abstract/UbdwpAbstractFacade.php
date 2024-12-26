<?php

namespace UsersBulkDeleteWithPreview\Abstract;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Abstract base class for a facade pattern.
 * Provides a mechanism for lazy-loading and static method calls.
 */
abstract class UbdwpAbstractFacade {
	/**
	 * Array of instances
	 *
	 * @var array
	 */
	private static array $instances = array();

	/**
	 * Get the singleton instance of the class.
	 *
	 * @param  string $class_name  The name of the class to instantiate.
	 *
	 * @return object The singleton instance of the class.
	 */
	public static function get_instance( string $class_name ): object {
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new $class_name();
		}

		return self::$instances[ $class_name ];
	}

	/**
	 * Handle static method calls by delegating them to the singleton instance.
	 *
	 * @param  string $name  The name of the method to call.
	 * @param  array  $arguments  The arguments to pass to the method.
	 *
	 * @return mixed The result of the method call.
	 * @throws Exception If the method does not exist in the class.
	 */
	public static function __callStatic( string $name, array $arguments ) {
		$class_name = static::get_class_name();
		$instance   = self::get_instance( $class_name );

		if ( method_exists( $instance, $name ) ) {
			return call_user_func_array(
				array( $instance, $name ),
				$arguments
			);
		}

		throw new Exception(
			sprintf(
			/* translators: %1$s is the method name; %2$s is the class name. */
				esc_html__(
					'Method %1$s does not exist in class %2$s.',
					'users-bulk-delete-with-preview'
				),
				esc_html( $name ),
				esc_html( $class_name )
			)
		);
	}

	/**
	 * Get the class name for creating an instance.
	 *
	 * @return string The class name.
	 */
	abstract protected static function get_class_name(): string;
}