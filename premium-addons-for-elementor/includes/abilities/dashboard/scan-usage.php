<?php
/**
 * Ability: Scan Premium Addons widget usage.
 *
 * A "dashboard" category ability. Reports which Premium Addons widgets are in
 * use across the site and how many times each is used. Thin adapter over
 * Admin_Helper::get_used_widgets(), which runs Elementor's Usage module
 * re-scan and filters the result to Premium Addons widgets. The re-scan
 * rebuilds Elementor's derived usage cache on every call (deletes and rewrites
 * the elementor_elements_usage option and per-post usage meta), so the ability
 * is not readonly — it is annotated non-destructive and idempotent (a recompute
 * yields the same derived data). Registered from
 * PremiumAddons\Includes\Abilities\Bootstrap on the wp_abilities_api_init hook.
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
		'description'         => __( 'Reports which Premium Addons widgets are in use across the site and how many times each is used. Widgets only — global features and addons are excluded. Returns an empty object when Elementor\'s Usage module is unavailable.', 'premium-addons-for-elementor' ),
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
