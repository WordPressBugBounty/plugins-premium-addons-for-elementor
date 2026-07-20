<?php
/**
 * Scan Usage.
 *
 * Reports which Premium Addons widgets are used on the site and how often.
 *
 * @package PremiumAddons
 */

namespace PremiumAddons\Includes\Abilities\Dashboard;

use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Abilities\Contracts\Ability_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Scan Usage ability handler.
 */
class Scan_Usage implements Ability_Handler {

	/**
	 * Get the short ability name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'scan-usage';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 */
	public function get_registration_args() {
		return array(
			'label'               => __( 'Scan Premium Addons Widget Usage', 'premium-addons-for-elementor' ),
			'description'         => __( 'Shows which Premium Addons widgets are used on the site and how often.', 'premium-addons-for-elementor' ),
			'category'            => 'pa-dashboard',
			'output_schema'       => array(
				'type'        => 'object',
				'description' => __( 'Premium Addons widget usage across the site.', 'premium-addons-for-elementor' ),
				'properties'  => array(
					'used' => array(
						'type'                 => 'object',
						'description'          => __( 'Map of Premium Addons widget key to the number of times it is used site-wide. Empty when no Premium Addons widgets are in use or the Usage module is unavailable.', 'premium-addons-for-elementor' ),
						'additionalProperties' => array(
							'type' => 'integer',
						),
					),
				),
			),
			'permission_callback' => function () {
				return Admin_Helper::check_user_can( 'manage_options' );
			},
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'    => false,
					'destructive' => false,
					'idempotent'  => true,
				),
			),
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array|null $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute( $input = null ) {

		return array(
			'used' => Admin_Helper::get_used_widgets(),
		);
	}
}
