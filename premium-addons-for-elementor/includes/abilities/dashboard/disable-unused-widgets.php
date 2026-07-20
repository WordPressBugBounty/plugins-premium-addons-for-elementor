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

use PremiumAddons\Includes\Abilities\Contracts\Ability_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Ability handler.
 */
class Disable_Unused_Widgets implements Ability_Handler {

	/**
	 * Get the short ability name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'disable-unused-widgets';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 */
	public function get_registration_args() {
		return array(
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
		);
	}

	/**
	 * Execute the ability.
	 *
	 * @param array|null $input Ability input.
	 * @return array|\WP_Error
	 */
	public function execute( $input = null ) {

		$dry_run = is_array( $input ) && ! empty( $input['dry_run'] );

		// Elementor reports usage by widget *name*; the settings store keys by
		// settings *key* (they differ for the premium-addon-* family). Diff in
		// name-space, then translate to keys so every id this ability returns
		// matches get-settings / update-setting.
		$name_to_key = array();
		foreach ( Admin_Helper::get_elements_list()['cat-1']['elements'] as $elem ) {
			if ( isset( $elem['name'], $elem['key'] ) && 'global' !== $elem['name'] ) {
				$name_to_key[ $elem['name'] ] = $elem['key'];
			}
		}

		$unused_names = array_diff(
			Admin_Helper::get_pa_elements_names(),
			array_keys( Admin_Helper::get_used_widgets() )
		);

		$unused = array();
		foreach ( $unused_names as $name ) {
			if ( isset( $name_to_key[ $name ] ) ) {
				$unused[] = $name_to_key[ $name ];
			}
		}

		$disabled = array();

		if ( ! $dry_run && ! empty( $unused ) ) {

			$result = Admin_Helper::update_elements_settings( array_fill_keys( $unused, false ) );

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
	}
}
