<?php
/**
 * Ability: Subscribe to the Premium Addons newsletter.
 *
 * A write ability in the "dashboard" category. Subscribes an email address to
 * the Premium Addons newsletter — the same list the dashboard's General-tab
 * form feeds. Thin adapter over the Admin_Helper::subscribe_newsletter_request()
 * service the AJAX handler also calls, so the two never drift. The service makes
 * an external HTTP request to premiumaddons.com, so it can fail for network
 * reasons unrelated to WordPress, and it is not idempotent — re-submitting the
 * same email re-hits the subscribe endpoint. Registered from
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
	'premium-addons/subscribe-newsletter',
	array(
		'label'               => __( 'Subscribe to Premium Addons Newsletter', 'premium-addons-for-elementor' ),
		'description'         => __( 'Subscribes an email address to the Premium Addons newsletter. Makes an external request to premiumaddons.com, so it can fail for network reasons; not idempotent — re-submitting the same email re-hits the subscribe endpoint.', 'premium-addons-for-elementor' ),
		'category'            => 'pa-dashboard',
		'input_schema'        => array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'email' => array(
					'type'        => 'string',
					'format'      => 'email',
					'description' => __( 'The email address to subscribe.', 'premium-addons-for-elementor' ),
				),
			),
			'required'             => array( 'email' ),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'The result of the subscribe request.', 'premium-addons-for-elementor' ),
			'properties'  => array(
				'success'  => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the request reached the newsletter endpoint successfully (HTTP 200).', 'premium-addons-for-elementor' ),
				),
				'response' => array(
					'type'                 => 'object',
					'description'          => __( 'The decoded response body from the newsletter endpoint.', 'premium-addons-for-elementor' ),
					'additionalProperties' => true,
				),
			),
		),
		'execute_callback'    => function ( $input ) {

			$email = isset( $input['email'] ) && is_string( $input['email'] ) ? sanitize_email( $input['email'] ) : '';

			if ( ! is_email( $email ) ) {
				return new \WP_Error(
					'premium_addons_invalid_email',
					__( 'A valid email address is required to subscribe to the newsletter.', 'premium-addons-for-elementor' )
				);
			}

			return Admin_Helper::subscribe_newsletter_request( $email );
		},
		'permission_callback' => function () {
			return Admin_Helper::check_user_can( 'manage_options' );
		},
		'meta'                => array(
			'show_in_rest' => true,
			'annotations'  => array(
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => false,
			),
		),
	)
);
