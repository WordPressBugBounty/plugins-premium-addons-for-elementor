<?php
/**
 * Ability: Get the Elementor element tree of a page, post or template.
 *
 * A read-only ability in the "discovery" category. Returns the Elementor
 * element tree for any Elementor document — page, post or elementor_library
 * template. A thin adapter over Elementor's document API: there is no single
 * Premium Addons service — the plugin already calls
 * Plugin::$instance->documents->get($id)->get_elements_data() inline in the
 * WooCommerce products module, the assets manager and Premium_Template_Tags —
 * so this reads the document directly, falling back to the raw _elementor_data
 * meta. Read-only in this context: get_elements_data()'s lazy convert-to-
 * Elementor write path only runs when editor->is_edit_mode() is true, which is
 * never the case for a REST/MCP invocation. By default each element is
 * summarized (id, elType, widgetType, a short settings summary); pass
 * include_settings to get the full per-element settings instead. Registered
 * from PremiumAddons\Includes\Abilities\Bootstrap on the wp_abilities_api_init
 * hook.
 *
 * @package PremiumAddons
 */

namespace PremiumAddons\Includes\Abilities\Discovery;

use PremiumAddons\Admin\Includes\Admin_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

wp_register_ability(
	'premium-addons/get-page-structure',
	array(
		'label'               => __( 'Get Page Structure', 'premium-addons-for-elementor' ),
		'description'         => __( 'Returns the Elementor element tree for a page, post or template. Each node carries its id, element type, widget type and a short settings summary, nested as on the page. Pass include_settings to get the full per-element settings instead of the summary. Use premium-addons/get-id-by-title to resolve a title to its post_id first.', 'premium-addons-for-elementor' ),
		'category'            => 'pa-discovery',
		'input_schema'        => array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'post_id' ),
			'properties'           => array(
				'post_id'          => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the page, post or template whose structure to read.', 'premium-addons-for-elementor' ),
				),
				'include_settings' => array(
					'type'        => 'boolean',
					'default'     => false,
					'description' => __( 'Return the full settings of every element instead of the short summary. Defaults to false — the full tree can be very large.', 'premium-addons-for-elementor' ),
				),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'The Elementor document and its element tree.', 'premium-addons-for-elementor' ),
			'properties'  => array(
				'post_id'   => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the document.', 'premium-addons-for-elementor' ),
				),
				'title'     => array(
					'type'        => 'string',
					'description' => __( 'The document title.', 'premium-addons-for-elementor' ),
				),
				'type'      => array(
					'type'        => 'string',
					'description' => __( 'The Elementor document type from the _elementor_template_type meta (e.g. wp-page, wp-post, page, section, container), falling back to the post type.', 'premium-addons-for-elementor' ),
				),
				'structure' => array(
					'type'        => 'array',
					'description' => __( 'The nested element tree. Each node is { id, elType, widgetType?, settings_summary | settings, elements? } where elements holds the node children recursively.', 'premium-addons-for-elementor' ),
					'items'       => array(
						'type'                 => 'object',
						'additionalProperties' => true,
					),
				),
			),
		),
		'execute_callback'    => function ( $input = null ) {

			if ( ! class_exists( '\Elementor\Plugin' ) ) {
				return new \WP_Error(
					'premium_addons_elementor_missing',
					__( 'Elementor is not active.', 'premium-addons-for-elementor' )
				);
			}

			$post_id = ! empty( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;

			if ( ! $post_id ) {
				return new \WP_Error(
					'premium_addons_missing_post_id',
					__( 'A post_id is required to read a page structure.', 'premium-addons-for-elementor' )
				);
			}

			$post = get_post( $post_id );

			if ( ! $post ) {
				return new \WP_Error(
					'premium_addons_post_not_found',
					/* translators: %d: post ID. */
					sprintf( __( 'No page/post found with ID %d.', 'premium-addons-for-elementor' ), $post_id )
				);
			}

			$edit_mode = get_post_meta( $post_id, '_elementor_edit_mode', true );

			if ( 'builder' !== $edit_mode && 'elementor_library' !== $post->post_type ) {
				return new \WP_Error(
					'premium_addons_not_elementor_document',
					/* translators: %d: post ID. */
					sprintf( __( 'The post with ID %d is not built with Elementor.', 'premium-addons-for-elementor' ), $post_id )
				);
			}

			$document = \Elementor\Plugin::$instance->documents->get( $post_id );
			$elements = $document ? $document->get_elements_data() : null;

			if ( empty( $elements ) ) {
				$raw      = get_post_meta( $post_id, '_elementor_data', true );
				$elements = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : $raw;
			}

			if ( ! is_array( $elements ) ) {
				$elements = array();
			}

			$include_settings = ! empty( $input['include_settings'] );

			$summarize_settings = function ( $settings ) {

				foreach ( $settings as $value ) {
					if ( is_string( $value ) ) {
						$value = trim( wp_strip_all_tags( $value ) );

						if ( strlen( $value ) > 3 ) {
							return mb_substr( $value, 0, 80 );
						}
					}
				}

				return '';
			};

			$build_tree = function ( $elements ) use ( &$build_tree, $include_settings, $summarize_settings ) {

				$tree = array();

				foreach ( $elements as $element ) {

					if ( ! is_array( $element ) ) {
						continue;
					}

					$node = array(
						'id'     => isset( $element['id'] ) ? $element['id'] : '',
						'elType' => isset( $element['elType'] ) ? $element['elType'] : '',
					);

					if ( ! empty( $element['widgetType'] ) ) {
						$node['widgetType'] = $element['widgetType'];
					}

					$settings = isset( $element['settings'] ) && is_array( $element['settings'] ) ? $element['settings'] : array();

					if ( $include_settings ) {
						$node['settings'] = $settings;
					} else {
						$node['settings_summary'] = $summarize_settings( $settings );
					}

					if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
						$node['elements'] = $build_tree( $element['elements'] );
					}

					$tree[] = $node;
				}

				return $tree;
			};

			$type = get_post_meta( $post_id, '_elementor_template_type', true );

			return array(
				'post_id'   => $post_id,
				'title'     => get_the_title( $post ),
				'type'      => $type ? $type : $post->post_type,
				'structure' => $build_tree( $elements ),
			);
		},
		'permission_callback' => function ( $input = null ) {

			$post_id = ! empty( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;

			// map_meta_cap() fires _doing_it_wrong for edit_post on a missing post,
			// so deny nonexistent IDs here — this also avoids leaking which IDs exist.
			if ( ! $post_id || ! get_post( $post_id ) ) {
				return false;
			}

			return Admin_Helper::check_user_can( 'edit_post', $post_id );
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
