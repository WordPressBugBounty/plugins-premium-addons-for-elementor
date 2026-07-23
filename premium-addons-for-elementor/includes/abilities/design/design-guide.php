<?php
/**
 * Design Guide.
 *
 * Ships the design guide to MCP clients as a prompt, and reminds the build
 * tools of the load-bearing rules inside their own results.
 *
 * @package PremiumAddons
 */

namespace PremiumAddons\Includes\Abilities\Design;

use PremiumAddons\Admin\Includes\Admin_Helper;

use WP\MCP\Domain\Prompts\McpPrompt;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Design guide delivery.
 */
class Design_Guide {

	/**
	 * MCP prompt name. Matches the name in the guide's front matter.
	 */
	const PROMPT_NAME = 'pafe-design';

	/**
	 * MCP tool names that carry the design note in their result.
	 */
	const BUILD_TOOLS = array(
		'premium-addons-insert-widget',
		'premium-addons-add-container',
		'premium-addons-add-flexbox',
	);

	/**
	 * Parsed guide file, or false when it could not be read.
	 *
	 * @var array|bool|null
	 */
	private static $guide = null;

	/**
	 * Get the design guide as an MCP prompt.
	 *
	 * @return McpPrompt|null
	 */
	public static function get_prompt() {

		$guide = self::get_guide();

		if ( ! $guide ) {
			return null;
		}

		$prompt = McpPrompt::fromArray(
			array(
				'name'        => self::PROMPT_NAME,
				'title'       => __( 'Premium Addons Design Guide', 'premium-addons-for-elementor' ),
				'description' => $guide['description'],
				'handler'     => function () use ( $guide ) {
					return array( 'text' => $guide['body'] );
				},
				'permission'  => function () {
					return Admin_Helper::check_user_can( 'edit_posts' );
				},
			)
		);

		return is_wp_error( $prompt ) ? null : $prompt;
	}

	/**
	 * Get the guide text without its front matter.
	 *
	 * @return string
	 */
	public static function get_body() {

		$guide = self::get_guide();

		return $guide ? $guide['body'] : '';
	}

	/**
	 * Add the design note to build tool results.
	 *
	 * @param mixed  $result    Tool execution result.
	 * @param array  $args      Tool arguments.
	 * @param string $tool_name MCP tool name.
	 * @return mixed
	 */
	public static function filter_tool_result( $result, $args, $tool_name ) {

		if ( ! is_array( $result ) || ! in_array( $tool_name, self::BUILD_TOOLS, true ) ) {
			return $result;
		}

		$result['design_note'] = self::get_note();

		return $result;
	}

	/**
	 * Get the just-in-time design note.
	 *
	 * Kept in lockstep with pafe-design.md and the rules ledger.
	 *
	 * @return string
	 */
	private static function get_note() {
		return __( 'NEVER ignore calling premium-addons/get-design-guide. Design check: build every color, font and size from the values premium-addons/get-global-settings and premium-addons/get-theme-styles returned — reuse those exact values across the page and never introduce a new palette, font or off-scale spacing mid-build. Read premium-addons/get-widget-schema (and get-addon-schema) before writing a widget\'s keys. Use containers, never sections or columns, and declare the mobile collapse on every multi-column container. Native Elementor only: no injected script, no external CSS or animation libraries. Keep the layout varied — no row of three identical cards. When the section is done, judge the rendered front end, not the stored settings, and fix off-token values, contrast below AA, an over-long headline, and unset critical controls. Call premium-addons/get-design-guide for the full guide.', 'premium-addons-for-elementor' );
	}

	/**
	 * Read and parse the guide file once.
	 *
	 * @return array|bool
	 */
	private static function get_guide() {

		if ( null !== self::$guide ) {
			return self::$guide;
		}

		self::$guide = false;

		$path = PREMIUM_ADDONS_PATH . 'includes/abilities/design/pafe-design.md';

		if ( ! is_readable( $path ) ) {
			return self::$guide;
		}

		$contents = file_get_contents( $path );

		if ( ! $contents ) {
			return self::$guide;
		}

		$body        = trim( $contents );
		$description = '';

		// The front matter is skill metadata, not prompt text: its description
		// becomes the prompt description, the rest becomes the prompt message.
		if ( preg_match( '/^---\R(.*?)\R---\R(.*)$/s', $body, $matches ) ) {
			$body = trim( $matches[2] );

			if ( preg_match( '/^description:\s*(.+)$/m', $matches[1], $description_match ) ) {
				$description = trim( $description_match[1] );
			}
		}

		self::$guide = array(
			'description' => $description,
			'body'        => $body,
		);

		return self::$guide;
	}
}
