<?php
/**
 * Check Elementor Element.
 *
 * Checks whether an Elementor element or widget is available on the site.
 *
 * @package PremiumAddons
 */

namespace PremiumAddons\Includes\Abilities\Discovery;

use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Abilities\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

wp_register_ability(
	'premium-addons/check-elementor-element',
	array(
		'label'               => __( 'Check Elementor Element', 'premium-addons-for-elementor' ),
		'description'         => __( 'Checks whether an Elementor element or widget is available on the site.', 'premium-addons-for-elementor' ),
		'category'            => 'pa-discovery',
		'input_schema'        => array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'element' ),
			'properties'           => array(
				'element' => array(
					'type'        => 'string',
					'description' => __( 'The element or widget type name to check (e.g. e-flexbox, container, premium-carousel).', 'premium-addons-for-elementor' ),
				),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'Whether the type is registered and where.', 'premium-addons-for-elementor' ),
			'properties'  => array(
				'exists' => array(
					'type'        => 'boolean',
					'description' => __( 'True when the type is registered on this site.', 'premium-addons-for-elementor' ),
				),
				'kind'   => array(
					'type'        => array( 'string', 'null' ),
					'enum'        => array( 'element', 'widget', null ),
					'description' => __( 'element for structural types (container, e-flexbox, section), widget for widgets, null when not registered.', 'premium-addons-for-elementor' ),
				),
				'title'  => array(
					'type'        => 'string',
					'description' => __( 'The human-readable title of the registered type, when available.', 'premium-addons-for-elementor' ),
				),
			),
		),
		'execute_callback'    => function ( $input = null ) {

			$error = Helpers::guard_elementor();

			if ( $error ) {
				return $error;
			}

			$name = isset( $input['element'] ) ? trim( (string) $input['element'] ) : '';

			if ( '' === $name ) {
				return new \WP_Error(
					'premium_addons_missing_element',
					__( 'An element type name is required.', 'premium-addons-for-elementor' )
				);
			}

			$element_type = \Elementor\Plugin::$instance->elements_manager->get_element_types( $name );

			if ( $element_type ) {
				return array(
					'exists' => true,
					'kind'   => 'element',
					'title'  => $element_type->get_title(),
				);
			}

			$widget_type = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $name );

			if ( $widget_type ) {
				return array(
					'exists' => true,
					'kind'   => 'widget',
					'title'  => $widget_type->get_title(),
				);
			}

			return array(
				'exists' => false,
				'kind'   => null,
			);
		},
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
	)
);
