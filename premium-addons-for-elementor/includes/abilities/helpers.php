<?php
/**
 * Shared helpers for the AI abilities.
 *
 * The single helper bag for every ability group: the Elementor-active guard,
 * Elementor document resolution (with raw _elementor_data fallback), the shared
 * per-post edit permission check, and the build-group tree/atomic utilities
 * (element-id generation, tree insert/modify, document save, and Elementor
 * control-value formatters). Resolved through the plugin autoloader.
 *
 * @package PremiumAddons
 */

namespace PremiumAddons\Includes\Abilities;

use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Helper_Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Class Helpers.
 *
 * The single helper bag for every ability group — the group-neutral guards plus
 * the build-group tree/atomic utilities.
 *
 * @since 4.11.75
 */
class Helpers {

	/**
	 * Guard: Elementor active.
	 *
	 * @return \WP_Error|null Error when Elementor is not active, null otherwise.
	 */
	public static function guard_elementor() {

		if ( ! Helper_Functions::check_elementor_version() ) {
			return new \WP_Error(
				'premium_addons_elementor_missing',
				__( 'Elementor is not active.', 'premium-addons-for-elementor' )
			);
		}

		return null;
	}

	/**
	 * Resolve an Elementor document and its element tree for editing.
	 *
	 * Validates the post exists and is an Elementor document, then returns the
	 * document plus its current elements array (raw _elementor_data fallback).
	 *
	 * @param int $post_id The post ID.
	 *
	 * @return array|\WP_Error [ \Elementor\Core\Base\Document, array $elements ] or an error.
	 */
	public static function resolve_document( $post_id ) {

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
				sprintf( __( 'The post with ID %d is not built with Elementor. Use premium-addons/create-page to create an Elementor document first.', 'premium-addons-for-elementor' ), $post_id )
			);
		}

		$document = \Elementor\Plugin::$instance->documents->get( $post_id );

		if ( ! $document ) {
			return new \WP_Error(
				'premium_addons_document_not_found',
				/* translators: %d: post ID. */
				sprintf( __( 'Elementor could not resolve a document for the post with ID %d.', 'premium-addons-for-elementor' ), $post_id )
			);
		}

		$elements = $document->get_elements_data();

		if ( empty( $elements ) ) {
			$raw      = get_post_meta( $post_id, '_elementor_data', true );
			$elements = is_string( $raw ) && '' !== $raw ? json_decode( $raw, true ) : $raw;
		}

		if ( ! is_array( $elements ) ) {
			$elements = array();
		}

		return array( $document, $elements );
	}

	/**
	 * Shared permission callback for per-post abilities.
	 *
	 * map_meta_cap() fires _doing_it_wrong for edit_post on a missing post, so
	 * deny nonexistent IDs here — this also avoids leaking which IDs exist.
	 *
	 * @param array|null $input The ability input.
	 *
	 * @return bool
	 */
	public static function can_edit_input_post( $input ) {

		$post_id = ! empty( $input['post_id'] ) ? absint( $input['post_id'] ) : 0;

		if ( ! $post_id || ! get_post( $post_id ) ) {
			return false;
		}

		return Admin_Helper::check_user_can( 'edit_post', $post_id );
	}

	/**
	 * Collect every element id in a tree.
	 *
	 * @param array $elements The element tree.
	 *
	 * @return array Flat list of element id strings.
	 */
	public static function collect_element_ids( $elements ) {

		$ids = array();

		foreach ( $elements as $element ) {

			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( ! empty( $element['id'] ) ) {
				$ids[] = (string) $element['id'];
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {
				$ids = array_merge( $ids, self::collect_element_ids( $element['elements'] ) );
			}
		}

		return $ids;
	}

	/**
	 * Generate a unique Elementor element id.
	 *
	 * Elementor's own Utils::generate_random_string() is dechex(rand()) with no
	 * collision guarantee, so generate a fixed 7-char hex id and retry against the
	 * ids already present in the tree.
	 *
	 * @param array $existing_ids Ids already used in the document.
	 *
	 * @return string A 7-character hex id unique within the document.
	 */
	public static function generate_element_id( $existing_ids ) {

		do {
			$id = dechex( wp_rand( 0x1000000, 0xFFFFFFF ) );
		} while ( in_array( $id, $existing_ids, true ) );

		return $id;
	}

	/**
	 * Insert a new element node into a tree.
	 *
	 * Empty parent_id inserts at the document root. Position is a zero-based index
	 * within the parent's children; omitted/null appends at the end.
	 *
	 * @param array    $elements  The element tree.
	 * @param string   $parent_id Parent element id, or '' for the root.
	 * @param int|null $position  Zero-based insert index, null to append.
	 * @param array    $node      The element node to insert.
	 * @param bool     $found     Set to true when the parent was found.
	 *
	 * @return array The modified tree.
	 */
	public static function insert_element( $elements, $parent_id, $position, $node, &$found ) {

		if ( '' === $parent_id ) {

			$found = true;

			if ( null === $position ) {
				$elements[] = $node;
			} else {
				array_splice( $elements, $position, 0, array( $node ) );
			}

			return $elements;
		}

		foreach ( $elements as $index => $element ) {

			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( isset( $element['id'] ) && (string) $element['id'] === $parent_id ) {

				$found    = true;
				$children = isset( $element['elements'] ) && is_array( $element['elements'] ) ? $element['elements'] : array();

				if ( null === $position ) {
					$children[] = $node;
				} else {
					array_splice( $children, $position, 0, array( $node ) );
				}

				$elements[ $index ]['elements'] = $children;

				return $elements;
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {

				$elements[ $index ]['elements'] = self::insert_element( $element['elements'], $parent_id, $position, $node, $found );

				if ( $found ) {
					return $elements;
				}
			}
		}

		return $elements;
	}

	/**
	 * Locate an element node by id and return a reference to it within the tree.
	 *
	 * The single tree traversal shared by find_element() (read) and
	 * apply_to_element() (write) so the two never walk the tree differently.
	 * Returns a reference to the matching node — so callers can read a copy of it
	 * or replace it in place — or a reference to null when no node carries the id.
	 * Stops at the first match.
	 *
	 * @param array  $elements   The element tree (by reference).
	 * @param string $element_id The element id to locate.
	 *
	 * @return array|null Reference to the matching node, or to null when absent.
	 */
	private static function &locate_element_ref( &$elements, $element_id ) {

		$miss = null;

		foreach ( $elements as &$element ) {

			if ( ! is_array( $element ) ) {
				continue;
			}

			if ( isset( $element['id'] ) && (string) $element['id'] === $element_id ) {
				return $element;
			}

			if ( ! empty( $element['elements'] ) && is_array( $element['elements'] ) ) {

				$nested = &self::locate_element_ref( $element['elements'], $element_id );

				if ( null !== $nested ) {
					return $nested;
				}

				unset( $nested );
			}
		}

		unset( $element );

		return $miss;
	}

	/**
	 * Find one element in a tree by id (read-only).
	 *
	 * Returns a copy of the matching node — id, elType, widgetType, settings,
	 * styles, nested elements — or null when the id is not present. Shares
	 * locate_element_ref() with apply_to_element() so a read and a write walk the
	 * tree identically.
	 *
	 * @param array  $elements   The element tree.
	 * @param string $element_id The element id to find.
	 *
	 * @return array|null The element node, or null when absent.
	 */
	public static function find_element( $elements, $element_id ) {

		$node = &self::locate_element_ref( $elements, $element_id );

		return $node;
	}

	/**
	 * Apply a modifier to one element in a tree.
	 *
	 * Locates the element with the given id and replaces it with the modifier's
	 * return value, leaving the rest of the tree untouched.
	 *
	 * @param array    $elements   The element tree.
	 * @param string   $element_id The element id to modify.
	 * @param callable $modify     function( array $element ): array.
	 * @param bool     $found      Set to true when the element was found.
	 *
	 * @return array The modified tree.
	 */
	public static function apply_to_element( $elements, $element_id, $modify, &$found ) {

		$node = &self::locate_element_ref( $elements, $element_id );

		if ( null !== $node ) {
			$found = true;
			$node  = call_user_func( $modify, $node );
		}

		return $elements;
	}

	/**
	 * Save an element tree through the Elementor document API.
	 *
	 * Document::save() replaces the whole _elementor_data tree, stamps
	 * _elementor_version and regenerates CSS. It returns false (without throwing)
	 * when the current user cannot edit the document, so surface that as an error.
	 * Atomic elements validate their settings/styles server-side on save and THROW
	 * on invalid data (Has_Atomic_Base::get_data_for_save), so catch and surface
	 * that too.
	 *
	 * @param \Elementor\Core\Base\Document $document The document.
	 * @param array                         $elements The full element tree to persist.
	 *
	 * @return true|\WP_Error
	 */
	public static function save_document( $document, $elements ) {

		try {
			$saved = $document->save( array( 'elements' => $elements ) );
		} catch ( \Exception $e ) {
			return new \WP_Error(
				'premium_addons_invalid_element_data',
				/* translators: %s: validation error detail. */
				sprintf( __( 'Elementor rejected the element data: %s', 'premium-addons-for-elementor' ), $e->getMessage() )
			);
		}

		if ( ! $saved ) {
			return new \WP_Error(
				'premium_addons_save_failed',
				__( 'Elementor rejected the document save. The current user may not be allowed to edit this post.', 'premium-addons-for-elementor' )
			);
		}

		return true;
	}

	/**
	 * Format a 4-side value as an Elementor dimensions control value.
	 *
	 * @param array $value { top?, right?, bottom?, left?, unit? } sides as numbers or strings.
	 *
	 * @return array Elementor dimensions value.
	 */
	public static function format_dimensions( $value ) {

		$sides = array( 'top', 'right', 'bottom', 'left' );
		$out   = array(
			'unit'     => ! empty( $value['unit'] ) ? $value['unit'] : 'px',
			'isLinked' => false,
		);

		foreach ( $sides as $side ) {
			$out[ $side ] = isset( $value[ $side ] ) ? (string) $value[ $side ] : '';
		}

		return $out;
	}

	/**
	 * Format a size value as an Elementor slider control value.
	 *
	 * @param array $value { size, unit? }.
	 *
	 * @return array Elementor slider value.
	 */
	public static function format_slider( $value ) {

		return array(
			'unit'  => ! empty( $value['unit'] ) ? $value['unit'] : 'px',
			'size'  => isset( $value['size'] ) ? $value['size'] : '',
			'sizes' => array(),
		);
	}

	/**
	 * Wrap a value as an atomic transformable envelope.
	 *
	 * Atomic (v4) elements store every prop as { $$type, value }.
	 *
	 * @param string $type  The prop type key (string, color, number, size, …).
	 * @param mixed  $value The inner value.
	 *
	 * @return array
	 */
	public static function atomic_value( $type, $value ) {

		return array(
			'$$type' => $type,
			'value'  => $value,
		);
	}

	/**
	 * Build an atomic size envelope.
	 *
	 * @param mixed  $size The size number.
	 * @param string $unit The unit, px by default.
	 *
	 * @return array
	 */
	public static function atomic_size( $size, $unit = 'px' ) {

		return self::atomic_value(
			'size',
			array(
				'size' => $size,
				'unit' => $unit ? $unit : 'px',
			)
		);
	}

	/**
	 * Build an atomic dimensions envelope from a top/right/bottom/left input.
	 *
	 * Maps the physical sides to the logical keys atomic styles use
	 * (top→block-start, right→inline-end, bottom→block-end, left→inline-start).
	 * Only the provided sides are included.
	 *
	 * @param array $value { top?, right?, bottom?, left?, unit? }.
	 *
	 * @return array
	 */
	public static function atomic_dimensions( $value ) {

		$map  = array(
			'top'    => 'block-start',
			'right'  => 'inline-end',
			'bottom' => 'block-end',
			'left'   => 'inline-start',
		);
		$unit = ! empty( $value['unit'] ) ? $value['unit'] : 'px';
		$out  = array();

		foreach ( $map as $side => $logical ) {
			if ( isset( $value[ $side ] ) ) {
				$out[ $logical ] = self::atomic_size( $value[ $side ], $unit );
			}
		}

		return self::atomic_value( 'dimensions', $out );
	}

	/**
	 * Map one Elementor (v3) control definition to a JSON Schema property.
	 *
	 * Pure per-control mapper for the get-widget-schema ability: turns a control's
	 * type into the JSON-Schema shape its stored value takes, carries the label as
	 * the description, the control default, and the control condition as depends_on.
	 * Returns null for structural/presentational controls that hold no settable
	 * value, so callers can skip them.
	 *
	 * @param array $control One entry from Controls_Stack::get_controls().
	 *
	 * @return array|null JSON Schema property, or null when the control holds no value.
	 */
	public static function control_to_schema( $control ) {

		$type  = isset( $control['type'] ) ? $control['type'] : '';
		$label = isset( $control['label'] ) ? $control['label'] : '';
		$prop  = array();

		switch ( $type ) {

			case 'text':
			case 'textarea':
			case 'wysiwyg':
			case 'code':
			case 'hidden':
			case 'select2':
			case 'date_time':
			case 'animation':
			case 'hover_animation':
				$prop['type'] = 'string';
				break;

			case 'number':
				$prop['type'] = 'number';
				break;

			case 'select':
			case 'choose':
				$prop['type'] = 'string';

				if ( ! empty( $control['options'] ) ) {
					$prop['enum'] = array_keys( $control['options'] );
				}
				break;

			case 'switcher':
			case 'popover_toggle':
				$prop['type'] = 'string';
				$prop['enum'] = array( 'yes', '' );
				break;

			case 'color':
				$prop['type']        = 'string';
				$prop['description'] = __( 'A CSS color value (hex, rgb, rgba, hsl).', 'premium-addons-for-elementor' );
				break;

			case 'slider':
				$prop['type']       = 'object';
				$prop['properties'] = array(
					'size' => array( 'type' => 'number' ),
					'unit' => array( 'type' => 'string' ),
				);
				break;

			case 'dimensions':
				$prop['type']       = 'object';
				$prop['properties'] = array(
					'top'      => array( 'type' => 'string' ),
					'right'    => array( 'type' => 'string' ),
					'bottom'   => array( 'type' => 'string' ),
					'left'     => array( 'type' => 'string' ),
					'unit'     => array( 'type' => 'string' ),
					'isLinked' => array( 'type' => 'boolean' ),
				);
				break;

			case 'url':
				$prop['type']       = 'object';
				$prop['properties'] = array(
					'url'         => array( 'type' => 'string' ),
					'is_external' => array( 'type' => 'string' ),
					'nofollow'    => array( 'type' => 'string' ),
				);
				break;

			case 'media':
			case 'gallery':
				$prop['type']       = 'object';
				$prop['properties'] = array(
					'url' => array( 'type' => 'string' ),
					'id'  => array( 'type' => 'integer' ),
				);
				break;

			case 'icon':
			case 'icons':
				$prop['type']       = 'object';
				$prop['properties'] = array(
					'value'   => array( 'type' => 'string' ),
					'library' => array( 'type' => 'string' ),
				);
				break;

			case 'typography':
			case 'text_shadow':
			case 'box_shadow':
			case 'border':
			case 'background':
			case 'css_filter':
			case 'text_stroke':
				// Group-control toggle — the real values live in companion {key}_* sub-controls.
				$prop['type']        = 'string';
				$prop['description'] = __( 'A group-control toggle; its values live in companion {key}_* controls (e.g. _font_family, _color, _width).', 'premium-addons-for-elementor' );
				break;

			case 'repeater':
				$prop['type']        = 'array';
				$prop['description'] = __( 'A list of repeater rows; each row is an object of the repeater fields.', 'premium-addons-for-elementor' );

				// Recurse over the row fields so each field maps to its own schema
				// (nested repeaters resolve naturally through this same mapper).
				$item_props = array();

				if ( ! empty( $control['fields'] ) && is_array( $control['fields'] ) ) {
					foreach ( $control['fields'] as $field_key => $field ) {

						// Fields carry their own name; the array key may be numeric.
						$field_name = isset( $field['name'] ) ? $field['name'] : $field_key;

						// Internal fields (_id and other _-prefixed row keys).
						if ( 0 === strpos( (string) $field_name, '_' ) ) {
							continue;
						}

						$mapped = self::control_to_schema( $field );

						if ( null !== $mapped ) {
							$item_props[ $field_name ] = $mapped;
						}
					}
				}

				$prop['items'] = array(
					'type'       => 'object',
					'properties' => (object) $item_props,
				);
				break;

			case 'section':
			case 'tab':
			case 'tabs':
			case 'divider':
			case 'heading':
			case 'raw_html':
			case 'deprecated_notice':
			case 'notice':
			case 'alert':
			case 'button':
				// Structural / presentational controls hold no settable value.
				return null;

			default:
				$prop['type'] = 'string';
				break;
		}

		if ( '' !== $label && empty( $prop['description'] ) ) {
			$prop['description'] = $label;
		}

		if ( isset( $control['default'] ) && '' !== $control['default'] && array() !== $control['default'] ) {
			$prop['default'] = $control['default'];
		}

		if ( ! empty( $control['condition'] ) ) {
			$prop['depends_on'] = $control['condition'];
		}

		// New-style nested conditions ({ relation, terms }). Kept in native shape —
		// flattening into the legacy map would lose OR/nesting/explicit operators.
		if ( ! empty( $control['conditions'] ) ) {
			$prop['depends_on'] = isset( $prop['depends_on'] )
				? array(
					// Both present: Elementor requires both to pass.
					'relation' => 'and',
					'terms'    => array( $prop['depends_on'], $control['conditions'] ),
				)
				: $control['conditions'];
		}

		return $prop;
	}

	/**
	 * Auto-set the unmet group-control prerequisite toggles for the provided keys.
	 *
	 * A group sub-control's value is inert until its master toggle is on —
	 * background_color renders nothing without background_background = classic. For
	 * each provided setting key, this reads the control's own condition to find the
	 * gating master toggle, and — when that toggle is a background/border/typography
	 * master present in the stack and the caller left it unset — fills it with the
	 * value that re-enables it, echoing what it set back to the caller.
	 *
	 * The enabling values are the documented fallback trio (background => classic,
	 * border => solid, typography => custom). Deriving them generically from
	 * Elementor's group-control metadata (groupType / groupPrefix) is deferred
	 * until those arg keys are verified against Elementor core.
	 *
	 * @param array $controls The control stack from Controls_Stack::get_controls().
	 * @param array $settings The caller-provided settings.
	 *
	 * @return array [ array $settings (with toggles filled), array $auto_set ].
	 */
	public static function apply_prerequisite_toggles( $controls, $settings ) {

		$enable_by_suffix = array(
			'typography' => 'custom',
			'border'     => 'solid',
			'background' => 'classic',
		);

		$auto_set = array();

		foreach ( array_keys( $settings ) as $key ) {

			if ( ! isset( $controls[ $key ]['condition'] ) || ! is_array( $controls[ $key ]['condition'] ) ) {
				continue;
			}

			foreach ( $controls[ $key ]['condition'] as $cond_key => $cond_value ) {

				// A trailing "!" marks a not-equal condition; the toggle key is the
				// same either way.
				$toggle_key   = rtrim( $cond_key, '!' );
				$enable_value = null;

				foreach ( $enable_by_suffix as $suffix => $value ) {
					if ( $suffix === substr( $toggle_key, - strlen( $suffix ) ) ) {
						$enable_value = $value;
						break;
					}
				}

				// Only fill a real group master toggle that exists in the stack —
				// never a content condition (icon_type, show_icon, …) — and never
				// override a toggle the caller set themselves.
				if ( null === $enable_value || ! isset( $controls[ $toggle_key ] ) || array_key_exists( $toggle_key, $settings ) ) {
					continue;
				}

				$settings[ $toggle_key ] = $enable_value;
				$auto_set[ $toggle_key ] = $enable_value;
			}
		}

		return array( $settings, $auto_set );
	}

	/**
	 * Collect the ids of the Premium Addons global-addon sections injected into a
	 * control stack.
	 *
	 * The global addons (Floating Effects, Tooltips, Display Conditions, Liquid
	 * Glass, … plus the Pro cursor/scroll/gradient family) are cross-widget
	 * extensions, not widget-owned settings — they flood every widget's Advanced
	 * tab. Each opens its section with the shared `pa-extension-icon` label marker,
	 * and a section-opener control's key IS its section id, so one pass over the
	 * stack finds them all with no hardcoded list — free and Pro alike.
	 *
	 * @param array $controls The control stack from Controls_Stack::get_controls().
	 *
	 * @return array Map of injected section id => true.
	 */
	public static function global_addon_section_ids( $controls ) {

		$sections = array();

		foreach ( $controls as $key => $control ) {

			$type  = isset( $control['type'] ) ? $control['type'] : '';
			$label = isset( $control['label'] ) ? $control['label'] : '';

			if ( 'section' === $type && false !== strpos( $label, 'pa-extension-icon' ) ) {
				$sections[ $key ] = true;
			}
		}

		return $sections;
	}

	/**
	 * Map one atomic (v4) prop type to a JSON Schema property.
	 *
	 * get_props_schema() returns Prop_Type OBJECTS, not arrays. Each exposes its
	 * transformable key (the $$type an update must envelope the value with) and a
	 * default via jsonSerialize(). Falls back gracefully for any shape that predates
	 * the object API.
	 *
	 * @param mixed $prop_type One entry from a type's get_props_schema().
	 *
	 * @return array JSON Schema property carrying the atomic prop $$type.
	 */
	public static function atomic_prop_to_schema( $prop_type ) {

		if ( is_object( $prop_type ) && method_exists( $prop_type, 'jsonSerialize' ) ) {

			$data  = $prop_type->jsonSerialize();
			$entry = array(
				'type'   => 'object',
				'$$type' => isset( $data['key'] ) ? $data['key'] : '',
			);

			if ( isset( $data['kind'] ) ) {
				$entry['kind'] = $data['kind'];
			}

			if ( array_key_exists( 'default', $data ) && null !== $data['default'] ) {
				$entry['default'] = $data['default'];
			}

			return $entry;
		}

		if ( is_array( $prop_type ) ) {
			return $prop_type;
		}

		return array( 'type' => 'object' );
	}
}
