<?php
/**
 * Ability: Clear Premium Addons dynamic assets.
 *
 * A write ability in the "dashboard" category. Clears generated dynamic CSS/JS
 * asset files — site-wide by default, or for a single page when post_id is
 * passed — and purges the related caches. Thin adapter over the
 * Assets_Manager::clear_dynamic_assets() service — the same clearing logic the
 * dashboard's AJAX "clear cached assets" action runs — so the ability, REST and
 * UI never drift. Side-effects are broader than Premium Addons files: it also
 * clears Elementor's file cache and fires litespeed_purge_all. Guarded to the
 * premium-assets-generator feature; returns an error when it is disabled, since
 * the feature is what generates the assets in the first place. Resolve a page's
 * title to its post_id first with the premium-addons/get-id-by-title ability.
 * Registered from PremiumAddons\Includes\Abilities\Bootstrap on the
 * wp_abilities_api_init hook.
 *
 * @package PremiumAddons
 */

namespace PremiumAddons\Includes\Abilities\Dashboard;

use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Assets_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

wp_register_ability(
	'premium-addons/clear-dynamic-assets',
	array(
		'label'               => __( 'Clear Premium Addons Dynamic Assets', 'premium-addons-for-elementor' ),
		'description'         => __( 'Clears generated dynamic CSS/JS asset files and purges the related caches (Elementor file cache and LiteSpeed). Clears every page site-wide by default, or a single page when post_id is given. Requires the Premium Addons Assets Generator feature to be enabled; returns an error when it is disabled. The files regenerate automatically on the next page load.', 'premium-addons-for-elementor' ),
		'category'            => 'pa-dashboard',
		'input_schema'        => array(
			'type'                 => 'object',
			'default'              => (object) array(),
			'additionalProperties' => false,
			'properties'           => array(
				'post_id' => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the page/post whose dynamic assets to clear. Omit to clear every page site-wide. Use premium-addons/get-id-by-title to resolve a page title to its ID.', 'premium-addons-for-elementor' ),
				),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'The result of the clear operation.', 'premium-addons-for-elementor' ),
			'properties'  => array(
				'cleared' => array(
					'type'        => 'boolean',
					'description' => __( 'Always true when the assets were cleared.', 'premium-addons-for-elementor' ),
				),
				'post_id' => array(
					'type'        => array( 'integer', 'null' ),
					'description' => __( 'The page that was cleared, or null when the assets were cleared site-wide.', 'premium-addons-for-elementor' ),
				),
			),
		),
		'execute_callback'    => function ( $input = null ) {

			// The schema top-level default arrives as an empty stdClass, not an array.
			$input = is_array( $input ) ? $input : array();

			$enabled = Admin_Helper::get_enabled_elements();

			if ( empty( $enabled['premium-assets-generator'] ) ) {
				return new \WP_Error(
					'premium_addons_assets_generator_disabled',
					__( 'The Assets Generator feature is disabled, so there are no dynamic assets to clear. Enable it from the Premium Addons dashboard first.', 'premium-addons-for-elementor' )
				);
			}

			$post_id = ! empty( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;

			if ( $post_id && ! get_post( $post_id ) ) {
				return new \WP_Error(
					'premium_addons_post_not_found',
					/* translators: %d: post ID. */
					sprintf( __( 'No page/post found with ID %d.', 'premium-addons-for-elementor' ), $post_id )
				);
			}

			Assets_Manager::clear_dynamic_assets( $post_id );

			return array(
				'cleared' => true,
				'post_id' => $post_id ? $post_id : null,
			);
		},
		'permission_callback' => function () {
			return Admin_Helper::check_user_can( 'manage_options' );
		},
		'meta'                => array(
			'show_in_rest' => true,
			'annotations'  => array(
				'readonly'    => false,
				'destructive' => true,
				'idempotent'  => true,
			),
		),
	)
);
