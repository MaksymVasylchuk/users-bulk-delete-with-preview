<?php

namespace UsersBulkDeleteWithPreview\Traits;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Trait IsaTraitSingleton
 *
 * Implements the Singleton design pattern to ensure only one instance
 * of a class using this trait exists throughout the application lifecycle.
 */
trait UbdwpTraitSingleton {

	/**
	 * Holds the single instance of the class.
	 *
	 * @var static
	 */
	private static $instance;

	/**
	 * Get the single instance of the class.
	 *
	 * This method checks if an instance already exists. If not, it creates one.
	 * Ensures that only one instance is used throughout the application.
	 *
	 * @return static The single instance of the class.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new static();
		}
		return self::$instance;
	}

	/**
	 * Constructor is private to prevent direct instantiation.
	 *
	 * Calls the `initialize` method for additional setup, which can be defined
	 * by the class using this trait.
	 */
	private function __construct() {
		$this->initialize();
	}

	/**
	 * Prevent cloning of the instance.
	 *
	 * This method is private to ensure the Singleton instance cannot be cloned.
	 */
	private function __clone() {}

	/**
	 * Prevent unserialization of the instance.
	 *
	 * This method is private to ensure the Singleton instance cannot be unserialized.
	 */
	public function __wakeup() {}

	/**
	 * Optional initialization method.
	 *
	 * This method can be implemented by the class using this trait to perform
	 * setup tasks during instantiation.
	 */
	protected function initialize() {}
}
