<?php
/**
 * Premium Addons Module Base.
 */

namespace PremiumAddons\Includes;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Module Base
 *
 * @since 0.0.1
 */
abstract class Module_Base {

	/**
	 * Reflection
	 *
	 * @var \ReflectionClass
	 */
	private $reflection;

	/**
	 * Get Name
	 *
	 * @since 0.0.1
	 */
	abstract public function get_name();

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->reflection = new \ReflectionClass( $this );

		add_action( 'elementor/widgets/register', array( $this, 'init_widgets' ) );
	}

	/**
	 * Init Widgets
	 *
	 * @since 0.0.1
	 */
	public function init_widgets() {

		$widget_manager = \Elementor\Plugin::instance()->widgets_manager;

		foreach ( $this->get_widgets() as $widget ) {

			$class_name = $this->reflection->getNamespaceName() . '\Widgets\\' . $widget;

			if ( $this->is_widget() ) {
				$widget_manager->register( new $class_name() );
			}
		}
	}

	/**
	 * Get Widgets
	 *
	 * @since 0.0.1
	 *
	 * @return array
	 */
	public function get_widgets() {
		return array();
	}
}
