<?php
/**
 * Create Elementor Template.
 *
 * Creates a new saved Elementor template (elementor_library post) ready to build into.
 *
 * @package PremiumAddons
 */

namespace PremiumAddons\Includes\Abilities\PagePostManagement;

use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Abilities\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

wp_register_ability(
	'premium-addons/create-elementor-template',
	array(
		'label'               => __( 'Create Elementor Template', 'premium-addons-for-elementor' ),
		'description'         => __( 'Creates a new saved Elementor template ready to edit with Elementor.', 'premium-addons-for-elementor' ),
		'category'            => 'pa-page-post-management',
		'input_schema'        => array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'title' ),
			'properties'           => array(
				'title'         => array(
					'type'        => 'string',
					'description' => __( 'The title of the new template.', 'premium-addons-for-elementor' ),
				),
				'template_type' => array(
					'type'        => 'string',
					'enum'        => array( 'container', 'section', 'page', 'header', 'footer', 'popup' ),
					'default'     => 'container',
					'description' => __( 'The Elementor template type. Defaults to container. header, footer and popup require Elementor Pro to be usable in the Theme Builder.', 'premium-addons-for-elementor' ),
				),
				'status'        => array(
					'type'        => 'string',
					'enum'        => array( 'publish', 'draft' ),
					'default'     => 'publish',
					'description' => __( 'The template status. Defaults to publish so it appears in the Templates library.', 'premium-addons-for-elementor' ),
				),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'The created template and its Elementor URLs.', 'premium-addons-for-elementor' ),
			'properties'  => array(
				'post_id'       => array(
					'type'        => 'integer',
					'description' => __( 'The ID of the created template.', 'premium-addons-for-elementor' ),
				),
				'title'         => array(
					'type'        => 'string',
					'description' => __( 'The title of the created template.', 'premium-addons-for-elementor' ),
				),
				'template_type' => array(
					'type'        => 'string',
					'description' => __( 'The Elementor template type that was assigned.', 'premium-addons-for-elementor' ),
				),
				'edit_url'      => array(
					'type'        => 'string',
					'description' => __( 'The Elementor editor URL for the new template.', 'premium-addons-for-elementor' ),
				),
				'preview_url'   => array(
					'type'        => 'string',
					'description' => __( 'The preview URL for the new template.', 'premium-addons-for-elementor' ),
				),
			),
		),
		'execute_callback'    => function ( $input = null ) {

			$error = Helpers::guard_elementor();

			if ( $error ) {
				return $error;
			}

			$title = isset( $input['title'] ) ? trim( $input['title'] ) : '';

			if ( '' === $title ) {
				return new \WP_Error(
					'premium_addons_missing_title',
					__( 'A title is required to create a template.', 'premium-addons-for-elementor' )
				);
			}

			$status        = ! empty( $input['status'] ) ? $input['status'] : 'publish';
			$template_type = ! empty( $input['template_type'] ) ? $input['template_type'] : 'container';

			$post_id = wp_insert_post(
				array(
					'post_title'  => $title,
					'post_status' => $status,
					'post_type'   => 'elementor_library',
					'meta_input'  => array(
						'_elementor_edit_mode'     => 'builder',
						'_elementor_template_type' => $template_type,
					),
				),
				true
			);

			if ( is_wp_error( $post_id ) ) {
				return $post_id;
			}

			// Register the template type as a term so it shows under the matching
			// Templates library filter, mirroring Elementor's own save flow.
			wp_set_object_terms( $post_id, $template_type, 'elementor_library_type' );

			// Initialize the Elementor document with an empty element tree so
			// _elementor_data / _elementor_version are written and CSS regenerates.
			$document = \Elementor\Plugin::$instance->documents->get( $post_id );

			if ( $document ) {
				$document->save( array( 'elements' => array() ) );
			}

			return array(
				'post_id'       => $post_id,
				'title'         => get_the_title( $post_id ),
				'template_type' => $template_type,
				'edit_url'      => $document ? $document->get_edit_url() : '',
				'preview_url'   => 'publish' === $status ? get_permalink( $post_id ) : get_preview_post_link( $post_id ),
			);
		},
		'permission_callback' => function () {
			return Admin_Helper::check_user_can( 'edit_posts' );
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
