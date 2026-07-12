<?php
/**
 * Disable Unused Widgets.
 *
 * Disables the Premium Addons widgets that are not used anywhere on the site.
 *
 * @package PremiumAddons
 */

namespace PremiumAddons\Includes\Abilities\Dashboard;

use PremiumAddons\Admin\Includes\Admin_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

wp_register_ability(
	'premium-addons/disable-unused-widgets',
	array(
		'label'               => __( 'Disable Unused Premium Addons Widgets', 'premium-addons-for-elementor' ),
		'description'         => __( 'Disables all Premium Addons widgets that are not used on the site.', 'premium-addons-for-elementor' ),
		'category'            => 'pa-dashboard',
		'input_schema'        => array(
			'type'                 => 'object',
			'default'              => (object) array(),
			'additionalProperties' => false,
			'properties'           => array(
				'dry_run' => array(
					'type'        => 'boolean',
					'default'     => false,
					'description' => __( 'When true, returns the unused-widgets list without disabling anything.', 'premium-addons-for-elementor' ),
				),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'The unused Premium Addons widgets and which of them this call disabled.', 'premium-addons-for-elementor' ),
			'properties'  => array(
				'unused'   => array(
					'type'        => 'array',
					'description' => __( 'Keys of every Premium Addons widget not currently used on the site (includes widgets that were already disabled).', 'premium-addons-for-elementor' ),
					'items'       => array(
						'type' => 'string',
					),
				),
				'disabled' => array(
					'type'        => 'array',
					'description' => __( 'Keys of the widgets this call switched from enabled to disabled. Empty on a dry run or when every unused widget was already disabled.', 'premium-addons-for-elementor' ),
					'items'       => array(
						'type' => 'string',
					),
				),
			),
		),
		'execute_callback'    => function ( $input = null ) {

			$dry_run = is_array( $input ) && ! empty( $input['dry_run'] );

			$unused = array_values(
				array_diff(
					Admin_Helper::get_pa_elements_names(),
					array_keys( Admin_Helper::get_used_widgets() )
				)
			);

			$disabled = array();

			if ( ! $dry_run && ! empty( $unused ) ) {

				// $unused holds widget names; update_elements_settings keys on the
				// settings key, which differs for the premium-addon-* family. Map
				// names back to their settings keys before writing, else those
				// widgets land in $unknown and never get disabled.
				$name_to_key = array();
				foreach ( Admin_Helper::get_elements_list()['cat-1']['elements'] as $elem ) {
					if ( isset( $elem['name'] ) && 'global' !== $elem['name'] ) {
						$name_to_key[ $elem['name'] ] = $elem['key'];
					}
				}

				$unused_keys = array();
				foreach ( $unused as $name ) {
					if ( isset( $name_to_key[ $name ] ) ) {
						$unused_keys[] = $name_to_key[ $name ];
					}
				}

				$result = Admin_Helper::update_elements_settings( array_fill_keys( $unused_keys, false ) );

				// Report only widgets this call actually switched off (previously enabled).
				foreach ( $result['updated'] as $change ) {
					if ( $change['previous_value'] ) {
						$disabled[] = $change['key'];
					}
				}
			}

			return array(
				'unused'   => $unused,
				'disabled' => $disabled,
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
