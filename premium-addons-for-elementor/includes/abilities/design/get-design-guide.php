<?php
/**
 * Get Design Guide.
 *
 * Returns the bundled design guide as a tool result, for clients that cannot
 * reach the MCP prompt.
 *
 * @package PremiumAddons
 */

namespace PremiumAddons\Includes\Abilities\Design;

use PremiumAddons\Admin\Includes\Admin_Helper;

use PremiumAddons\Includes\Abilities\Contracts\Ability_Handler;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Ability handler.
 */
class Get_Design_Guide implements Ability_Handler {

	/**
	 * Get the short ability name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'get-design-guide';
	}

	/**
	 * Get the ability registration arguments.
	 *
	 * @return array
	 */
	public function get_registration_args() {
		return array(
			'label'               => __( 'Get Design Guide', 'premium-addons-for-elementor' ),
			'description'         => __( 'Returns the Premium Addons page design guide.', 'premium-addons-for-elementor' ),
			'category'            => 'pa-discovery',
			'output_schema'       => array(
				'type'        => 'object',
				'description' => __( 'The design guide.', 'premium-addons-for-elementor' ),
				'properties'  => array(
					'guide' => array(
						'type'        => 'string',
						'description' => __( 'The full design guide, in Markdown. Read it before building or restyling any page or section, and follow it for the whole build.', 'premium-addons-for-elementor' ),
					),
				),
			),
			'permission_callback' => function () {
				return Admin_Helper::check_user_can( 'edit_posts' );
			},
			'meta'                => array(
				'show_in_rest' => true,
				'annotations'  => array(
					'readonly'    => true,
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

		$guide = Design_Guide::get_body();

		if ( '' === $guide ) {
			return new \WP_Error(
				'premium_addons_design_guide_unreadable',
				__( 'The design guide file could not be read.', 'premium-addons-for-elementor' )
			);
		}

		return array( 'guide' => $guide );
	}
}
