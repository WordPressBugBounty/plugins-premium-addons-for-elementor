<?php
/**
 * Get Widget Schema.
 *
 * Returns the settings schema — every control and its type — for a widget or
 * structural element type, so agents build with the right keys instead of
 * guessing them.
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
	'premium-addons/get-widget-schema',
	array(
		'label'               => __( 'Get Widget Schema', 'premium-addons-for-elementor' ),
		'description'         => __( 'Returns the settings schema of a widget or structural element type.', 'premium-addons-for-elementor' ),
		'category'            => 'pa-discovery',
		'input_schema'        => array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'element' ),
			'properties'           => array(
				'element' => array(
					'type'        => 'string',
					'description' => __( 'The widget or element type name to introspect (e.g. premium-carousel, heading, container, e-flexbox).', 'premium-addons-for-elementor' ),
				),
				'tab'     => array(
					'type'        => 'string',
					'enum'        => array( 'content', 'style', 'advanced', 'all' ),
					'default'     => 'all',
					'description' => __( 'Which controls tab to include for classic (v3) types. Ignored for atomic (v4) types, which have no tabs. Defaults to all.', 'premium-addons-for-elementor' ),
				),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'The element type and its settings schema.', 'premium-addons-for-elementor' ),
			'properties'  => array(
				'element'   => array(
					'type'        => 'string',
					'description' => __( 'The type name that was introspected.', 'premium-addons-for-elementor' ),
				),
				'kind'      => array(
					'type'        => 'string',
					'enum'        => array( 'element', 'widget' ),
					'description' => __( 'element for structural types (container, e-flexbox, section), widget for widgets.', 'premium-addons-for-elementor' ),
				),
				'title'     => array(
					'type'        => 'string',
					'description' => __( 'The human-readable title of the type.', 'premium-addons-for-elementor' ),
				),
				'is_atomic' => array(
					'type'        => 'boolean',
					'description' => __( 'True when the type is an Elementor v4 atomic type — its schema keys are content props, and values are stored as { $$type, value } envelopes; visual props live on the styles map, not here.', 'premium-addons-for-elementor' ),
				),
				'tab'       => array(
					'type'        => 'string',
					'description' => __( 'The tab the classic schema was filtered to (all for atomic types).', 'premium-addons-for-elementor' ),
				),
				'schema'    => array(
					'type'                 => 'object',
					'additionalProperties' => true,
					'description'          => __( 'A JSON Schema object. Its properties map each control/prop key to its type, and — where relevant — enum, default and depends_on (the control\'s visibility prerequisite). depends_on is one of two shapes: a flat { key: value, "key!": value } map (implicit AND; "!" suffix means not-equal, an array value means in/not-in), or a nested { relation, terms } tree with explicit per-term operators (===, !==, in, <, contains). Filtered to the type\'s own settings: internal (_-prefixed) controls, Elementor-injected common sections (motion effects, transform, background, responsive, custom CSS) and the Premium Addons global-addon sections (Floating Effects, Tooltips, Display Conditions, …) are all removed.', 'premium-addons-for-elementor' ),
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
					__( 'An element or widget type name is required.', 'premium-addons-for-elementor' )
				);
			}

			$allowed_tabs = array( 'content', 'style', 'advanced', 'all' );
			$tab          = isset( $input['tab'] ) ? (string) $input['tab'] : 'all';

			if ( ! in_array( $tab, $allowed_tabs, true ) ) {
				$tab = 'all';
			}

			// Force widget registration — on a REST/MCP request elementor/widgets/register
			// may not have fired yet, so an un-primed lookup would miss.
			\Elementor\Plugin::$instance->widgets_manager->get_widget_types();

			// Resolve structural elements first, then widgets — same order as
			// premium-addons/check-elementor-element.
			$type_object = \Elementor\Plugin::$instance->elements_manager->get_element_types( $name );
			$kind        = 'element';

			if ( ! $type_object ) {
				$type_object = \Elementor\Plugin::$instance->widgets_manager->get_widget_types( $name );
				$kind        = 'widget';
			}

			// Disabled Premium Addons widgets are never registered, so this is also
			// how the build abilities discover an unavailable type.
			if ( ! $type_object ) {
				return new \WP_Error(
					'premium_addons_element_not_registered',
					/* translators: %s: element or widget type name. */
					sprintf( __( 'No element or widget type %s is registered on this site. A Premium Addons widget disabled in the dashboard is not registered.', 'premium-addons-for-elementor' ), $name )
				);
			}

			$is_atomic = class_exists( '\Elementor\Modules\AtomicWidgets\Utils\Utils' )
				&& \Elementor\Modules\AtomicWidgets\Utils\Utils::is_atomic( $type_object );

			$properties = array();

			if ( $is_atomic ) {

				// Atomic types have no controls/tabs — the props schema is objects
				// keyed by content prop, each carrying its $$type.
				if ( method_exists( $type_object, 'get_props_schema' ) ) {

					$props = $type_object::get_props_schema();

					if ( is_array( $props ) ) {
						foreach ( $props as $key => $prop_type ) {
							$properties[ $key ] = Helpers::atomic_prop_to_schema( $prop_type );
						}
					}
				}
			} else {

				$controls = $type_object->get_controls();

				// Premium Addons global-addon sections (Floating Effects, Tooltips,
				// Display Conditions, Liquid Glass, and the Pro cursor/scroll/gradient
				// family) are injected into every widget's Advanced tab — not
				// widget-owned, so always excluded.
				$pa_sections = Helpers::global_addon_section_ids( $controls );

				// Elementor-injected common sections (Advanced tab) — likewise not
				// widget-owned. The private _-prefixed sections (transform, background,
				// responsive, …) are caught by the section prefix below.
				$elementor_sections = array( 'section_effects', 'section_custom_css' );

				foreach ( $controls as $key => $control ) {

					// Internal controls (_padding, _element_id, …).
					if ( 0 === strpos( $key, '_' ) ) {
						continue;
					}

					$control_section = isset( $control['section'] ) ? $control['section'] : '';

					// Elementor-injected sections: the private _-prefixed ones plus the
					// two public ones (motion/entrance effects, custom CSS).
					if ( 0 === strpos( $control_section, '_' ) || in_array( $control_section, $elementor_sections, true ) ) {
						continue;
					}

					// Premium Addons global-addon sections.
					if ( isset( $pa_sections[ $control_section ] ) ) {
						continue;
					}

					$control_tab = isset( $control['tab'] ) ? $control['tab'] : '';

					if ( 'all' !== $tab && $control_tab !== $tab ) {
						continue;
					}

					$mapped = Helpers::control_to_schema( $control );

					if ( null !== $mapped ) {
						$properties[ $key ] = $mapped;
					}
				}
			}

			return array(
				'element'   => $name,
				'kind'      => $kind,
				'title'     => method_exists( $type_object, 'get_title' ) ? $type_object->get_title() : '',
				'is_atomic' => $is_atomic,
				'tab'       => $is_atomic ? 'all' : $tab,
				'schema'    => array(
					'type'       => 'object',
					'properties' => (object) $properties,
				),
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
