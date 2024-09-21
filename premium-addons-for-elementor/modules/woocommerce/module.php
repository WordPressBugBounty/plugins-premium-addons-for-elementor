<?php
/**
 * Class: Module
 * Name: Woocommerce
 * Slug: premium-woocommerce
 * PA WooCommerce Module.
 *
 * @package PA
 */

namespace PremiumAddons\Modules\Woocommerce;

use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Module_Base;
use PremiumAddons\Modules\Woocommerce\Modules\Products_Module;
use PremiumAddons\Modules\Woocommerce\Modules\CTA_Module;
use PremiumAddons\Modules\Woocommerce\Modules\categories_Module;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // If this file is called directly, abort.
}

/**
 * Class Module.
 */
class Module extends Module_Base {

	/**
	 * Class object
	 *
	 * @var instance
	 */
	private static $instance = null;

	/**
	 * Module should load or not.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @return bool true|false.
	 */
	public static function is_enable() {
		return true;
	}

	/**
	 * Get Module Name.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @return string Module name.
	 */
	public function get_name() {
		return 'woocommerce';
	}

	/**
	 * Get Widgets.
	 *
	 * @since 4.7.0
	 * @access public
	 *
	 * @return array Widgets.
	 */
	public function get_widgets() {
		return array(
			'Woo_Products',
			'Woo_Categories',
			'Woo_CTA',
		);
	}

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct();

		// Load individual widget modules.
		$this->load_modules();
	}

	/**
	 * Load individual widget modules.
	 *
	 * @since 4.7.0
	 * @access public
	 */
	public function load_modules() {

		$enabled_elements = Admin_Helper::get_enabled_elements();

		if ( isset( $enabled_elements['woo-products'] ) && $enabled_elements['woo-products'] ) {
			Products_Module::get_instance();
		}

		if ( isset( $enabled_elements['woo-cta'] ) && $enabled_elements['woo-cta'] ) {
			CTA_Module::get_instance();
		}

		if ( isset( $enabled_elements['woo-categories'] ) && $enabled_elements['woo-categories'] ) {
			Categories_Module::get_instance();
		}
	}

	/**
	 * Instance
	 *
	 * @return object self::$instance
	 */
	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
