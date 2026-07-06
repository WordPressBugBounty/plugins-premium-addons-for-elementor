<?php
/**
 * Ability: Get post/page ID by title.
 *
 * A read-only ability in the "discovery" category. Resolves any post or page to
 * its ID by its title. A thin adapter over core WordPress: there is no reusable
 * Premium Addons resolver — Premium_Template_Tags::get_id_by_title() is hardwired
 * to the elementor_library post type and AJAX_Helper::get_posts_list() is
 * AJAX-coupled and returns whole lists — so this queries WP_Query directly.
 * Never get_page_by_title(): deprecated since WordPress 6.2. Registered from
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
	'premium-addons/get-id-by-title',
	array(
		'label'               => __( 'Get Post/Page ID by Title', 'premium-addons-for-elementor' ),
		'description'         => __( 'Resolves any post or page to its ID by its title. Titles are not unique — especially across post types — so the first match is returned; pass post_type to disambiguate. Returns null when nothing matches.', 'premium-addons-for-elementor' ),
		'category'            => 'pa-discovery',
		'input_schema'        => array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'title' ),
			'properties'           => array(
				'title'     => array(
					'type'        => 'string',
					'description' => __( 'The exact title of the post or page to resolve.', 'premium-addons-for-elementor' ),
				),
				'post_type' => array(
					'type'        => 'string',
					'default'     => 'page',
					'description' => __( 'The post type to search. A specific type (e.g. page, post) or "any" to search every public type. Defaults to page.', 'premium-addons-for-elementor' ),
				),
			),
		),
		'output_schema'       => array(
			'type'        => 'object',
			'description' => __( 'The resolved post ID, or null when no post matches the title.', 'premium-addons-for-elementor' ),
			'properties'  => array(
				'id' => array(
					'type'        => array( 'integer', 'null' ),
					'description' => __( 'The ID of the first matching post, or null when there is no match.', 'premium-addons-for-elementor' ),
				),
			),
		),
		'execute_callback'    => function ( $input = null ) {

			$title     = isset( $input['title'] ) ? trim( $input['title'] ) : '';
			$post_type = ! empty( $input['post_type'] ) ? $input['post_type'] : 'page';

			if ( '' === $title ) {
				return array( 'id' => null );
			}

			$query = new \WP_Query(
				array(
					'title'                  => $title,
					'post_type'              => $post_type,
					'posts_per_page'         => 1,
					'fields'                 => 'ids',
					'no_found_rows'          => true,
					'ignore_sticky_posts'    => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			return array(
				'id' => ! empty( $query->posts ) ? $query->posts[0] : null,
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
