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

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

wp_register_ability(
	'premium-addons/scan-usage',
	array(
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
		'execute_callback'    => function () {

			return array(
				'used' => Admin_Helper::get_used_widgets(),
			);
		},
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
	)
);
