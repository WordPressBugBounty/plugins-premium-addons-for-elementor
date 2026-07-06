<?php
/**
 * Ability: Read the active Elementor kit's global design tokens.
 *
 * A read-only ability in the "discovery" category. Returns the active Elementor
 * kit's global colors, global typography and key layout tokens. A thin adapter
 * over Elementor: there is no Premium Addons wrapper — the plugin calls
 * Plugin::$instance->kits_manager->get_active_kit() inline in
 * widgets/premium-grid.php — so this reads the active kit's settings directly.
 * These are Elementor kit tokens, not Premium Addons' own global features.
 * Returns a WP_Error when there is no active kit. Registered from
 * PremiumAddons\Includes\Abilities\Bootstrap on the wp_abilities_api_init hook.
 *
 * @package PremiumAddons
 */

namespace PremiumAddons\Includes\Abilities\Discovery;

use PremiumAddons\Admin\Includes\Admin_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

wp_register_ability(
	'premium-addons/get-global-settings',
	array(
		'label'               => __( 'Get Elementor Global Settings', 'premium-addons-for-elementor' ),
		'description'         => __( "Reads the active Elementor kit's global design tokens: global colors (system and custom), global typography (system and custom) and key layout settings (content width, space between widgets). These are Elementor kit tokens, not Premium Addons global features. Returns an error when there is no active kit.", 'premium-addons-for-elementor' ),
		'category'            => 'pa-discovery',
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'The active Elementor kit global design tokens.', 'premium-addons-for-elementor' ),
			'properties'  => array(
				'colors'     => array(
					'type'        => 'object',
					'description' => __( 'Global colors, split into system and custom sets. Each entry carries its _id, title and color value.', 'premium-addons-for-elementor' ),
					'properties'  => array(
						'system' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'additionalProperties' => true,
							),
						),
						'custom' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'additionalProperties' => true,
							),
						),
					),
				),
				'typography' => array(
					'type'        => 'object',
					'description' => __( 'Global typography, split into system and custom sets. Each entry carries its _id, title and typography values.', 'premium-addons-for-elementor' ),
					'properties'  => array(
						'system' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'additionalProperties' => true,
							),
						),
						'custom' => array(
							'type'  => 'array',
							'items' => array(
								'type'                 => 'object',
								'additionalProperties' => true,
							),
						),
					),
				),
				'settings'   => array(
					'type'                 => 'object',
					'description'          => __( 'Key layout tokens (content width, space between widgets).', 'premium-addons-for-elementor' ),
					'additionalProperties' => true,
				),
			),
		),
		'execute_callback'    => function () {

			if ( ! class_exists( '\Elementor\Plugin' ) ) {
				return new \WP_Error(
					'premium_addons_elementor_missing',
					__( 'Elementor is not active.', 'premium-addons-for-elementor' )
				);
			}

			$kit = \Elementor\Plugin::$instance->kits_manager->get_active_kit();

			if ( ! $kit || ! $kit->get_main_id() ) {
				return new \WP_Error(
					'premium_addons_no_active_kit',
					__( 'There is no active Elementor kit.', 'premium-addons-for-elementor' )
				);
			}

			$kit_settings = $kit->get_settings();

			$get = function ( $key ) use ( $kit_settings ) {
				return isset( $kit_settings[ $key ] ) && is_array( $kit_settings[ $key ] ) ? $kit_settings[ $key ] : array();
			};

			return array(
				'colors'     => array(
					'system' => $get( 'system_colors' ),
					'custom' => $get( 'custom_colors' ),
				),
				'typography' => array(
					'system' => $get( 'system_typography' ),
					'custom' => $get( 'custom_typography' ),
				),
				'settings'   => array(
					'container_width'       => isset( $kit_settings['container_width'] ) ? $kit_settings['container_width'] : null,
					'space_between_widgets' => isset( $kit_settings['space_between_widgets'] ) ? $kit_settings['space_between_widgets'] : null,
				),
			);
		},
		'permission_callback' => function () {
			return Admin_Helper::check_user_can( 'manage_options' );
		},
		'meta'                => array(
			'show_in_rest' => true,
			'annotations'  => array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			),
		),
	)
);
