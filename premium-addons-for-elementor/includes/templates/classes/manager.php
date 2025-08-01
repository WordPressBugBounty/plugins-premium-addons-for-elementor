<?php

namespace PremiumAddons\Includes\Templates\Classes;

use PremiumAddons\Includes\Templates;
use PremiumAddons\Admin\Includes\Admin_Helper;
use PremiumAddons\Includes\Helper_Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Premium_Templates_Manager' ) ) {

	/**
	 * Premium Templates Manager.
	 *
	 * Templates manager class handles all templates library insertion
	 *
	 * @since 3.6.0
	 */
	class Premium_Templates_Manager {

		private static $instance = null;

		private $sources = array();

		/**
		 * Premium_Templates_Manager constructor.
		 *
		 * initialize required hooks for templates.
		 *
		 * @since 3.6.0
		 * @access public
		 */
		public function __construct() {

			// Register AJAX hooks
			add_action( 'wp_ajax_premium_get_templates', array( $this, 'get_templates' ) );
			add_action( 'wp_ajax_get_papro_license_status', array( $this, 'get_papro_license_status' ) );
			add_action( 'wp_ajax_premium_inner_template', array( $this, 'insert_inner_template' ) );

			add_action( 'wp_ajax_get_pa_element_data', array( $this, 'get_pa_element_data' ) );

			add_action( 'elementor/ajax/register_actions', array( $this, 'register_ajax_actions' ), 20 );

			$this->register_sources();

			add_filter( 'premium-templates-core/assets/editor/localize', array( $this, 'localize_tabs' ) );
		}

		/**
		 * Get PA Pro License Status
		 *
		 * Get the license status for Premium Addons Pro.
		 *
		 * @since 4.11.24
		 * @access public
		 */
		public function get_papro_license_status() {

			if ( ! Helper_Functions::check_papro_version() ) {
				return;
			}

			$key = Templates\premium_templates()->config->get( 'key' );

			if ( ! $key ) {
				return;
			}

			$ch = curl_init();

			curl_setopt( $ch, CURLOPT_URL, "https://my.leap13.com/?edd_action=check_license&license=$key&item_id=361" );
			curl_setopt( $ch, CURLOPT_POST, true );
			curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
			curl_setopt( $ch, CURLOPT_TIMEOUT, 40 );
			curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );

			$response_body = curl_exec( $ch );
			$response_code = curl_getinfo( $ch, CURLINFO_HTTP_CODE );
			$curl_error    = curl_error( $ch );

			if ( defined( 'CURLOPT_IPRESOLVE' ) && defined( 'CURL_IPRESOLVE_V4' ) ) {
				curl_setopt( $ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
			}

			if ( curl_errno( $ch ) || $response_code !== 200 ) {
				curl_close( $ch );
				return;
			}

			$body = json_decode( $response_body, true );

			// Close cURL session
			curl_close( $ch );

			if ( isset( $body['license'] ) ) {
				wp_send_json_success( $body['license'] );
			} else {
				return;
			}
		}

		/**
		 * Localize tabs
		 *
		 * Add tabs data to localize object
		 *
		 * @since 3.6.0
		 * @access public
		 *
		 * @return [type] [description]
		 */
		public function localize_tabs( $data ) {

			$tabs    = $this->get_template_tabs();
			$ids     = array_keys( $tabs );
			$default = $ids[0];

			$data['tabs']       = $tabs;
			$data['defaultTab'] = $default;

			return $data;
		}

		/**
		 * Register sources
		 *
		 * Register templates sources.
		 *
		 * @since 3.6.0
		 * @access public
		 *
		 * @return void
		 */
		public function register_sources() {

			require PREMIUM_ADDONS_PATH . 'includes/templates/sources/base.php';

			$namespace = str_replace( 'Classes', 'Sources', __NAMESPACE__ );

			$sources = array(
				'premium-api' => $namespace . '\Premium_Templates_Source_Api',
			);

			foreach ( $sources as $key => $class ) {

				require PREMIUM_ADDONS_PATH . 'includes/templates/sources/' . $key . '.php';

				$this->add_source( $key, $class );
			}
		}

		/**
		 * Get template tabs
		 *
		 * Get tabs for the library.
		 *
		 * @since 3.6.0
		 * @access public
		 */
		public function get_template_tabs() {

			$tabs = Templates\premium_templates()->types->get_types_for_popup();

			return $tabs;
		}

		/**
		 * Get template tabs
		 *
		 * Get tabs for the library.
		 *
		 * @since 3.6.0
		 * @access public
		 *
		 * @param $key source key
		 * @param $class source class
		 */
		public function add_source( $key, $class ) {
			$this->sources[ $key ] = new $class();
		}

		/**
		 * Returns needed source instance
		 *
		 * @return object
		 */
		public function get_source( $slug = null ) {
			return isset( $this->sources[ $slug ] ) ? $this->sources[ $slug ] : false;
		}


		/**
		 * Get template
		 *
		 * Get templates grid data.
		 *
		 * @since 3.6.0
		 * @access public
		 */
		public function get_templates() {

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error();
			}

			$tab     = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : '';
			$tabs    = $this->get_template_tabs();
			$sources = $tabs[ $tab ]['sources'];

			if ( 'premium_container' === $tab ) {
				$tab = 'premium_section';
			}

			$result = array(
				'templates'  => array(),
				'categories' => array(),
				'keywords'   => array(),
			);

			foreach ( $sources as $source_slug ) {

				$source = isset( $this->sources[ $source_slug ] ) ? $this->sources[ $source_slug ] : false;

				if ( $source ) {
					$result['templates']  = array_merge( $result['templates'], $source->get_items( $tab ) );
					$result['categories'] = array_merge( $result['categories'], $source->get_categories( $tab ) );
					$result['keywords']   = array_merge( $result['keywords'], $source->get_keywords( $tab ) );
				}
			}

			$all_cats = array(
				array(
					'slug'  => '',
					'title' => __( 'All', 'premium-addons-for-elementor' ),
				),
			);

			if ( ! empty( $result['categories'] ) ) {
				$result['categories'] = array_merge( $all_cats, $result['categories'] );
			}

			wp_send_json_success( $result );
		}

		/**
		 * Get PA Element Name
		 *
		 * Gets premium element info.
		 *
		 * @since 4.10.49
		 * @access public
		 */
		public function get_pa_element_data() {

			if ( ! isset( $_GET['element'] ) ) {
				wp_send_json_error();
			}

			$key = isset( $_GET['element'] ) ? sanitize_text_field( wp_unslash( $_GET['element'] ) ) : '';

			$info = Admin_Helper::get_info_by_key( $key );

			if ( ! $info ) {
				wp_send_json_error();
			}

			$url = add_query_arg(
				array(
					'page'   => 'premium-addons',
					'search' => $info['title'],
					'#tab'   => 'elements',
				),
				esc_url( admin_url( 'admin.php' ) )
			);

			$demo_link = strstr( $info['demo'], '/?', true );

			$demo_link = Helper_Functions::get_campaign_link( $demo_link, 'template-link', 'wp-editor', 'template-issues' );

			$data = array(
				'name'      => $info['title'],
				'widgetURL' => $demo_link,
				'url'       => $url,
			);

			wp_send_json_success( $data );
		}

		/**
		 * Insert inner template
		 *
		 * Insert an inner template before insert the parent one.
		 *
		 * @since 3.6.0
		 * @access public
		 */
		public function insert_inner_template() {

			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error();
			}

			$template_id = isset( $_REQUEST['template'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['template'] ) ) : false;

			if ( ! $template_id ) {
				wp_send_json_error();
			}

			$source       = $this->sources['premium-api'];
			$insert_media = isset( $_REQUEST['withMedia'] ) ? $_REQUEST['withMedia'] : true;

			if ( ! $source || ! $template_id ) {
				wp_send_json_error();
			}

			$template_data = $source->get_item( $template_id, $insert_media );

			if ( ! empty( $template_data['content'] ) ) {

				$template_title = isset( $_REQUEST['title'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['title'] ) ) : 'Template' . $template_id;

				wp_insert_post(
					array(
						'post_type'   => 'elementor_library',
						'post_title'  => $template_title,
						'post_status' => 'publish',
						'meta_input'  => array(
							'_elementor_data'          => $template_data['content'],
							'_elementor_edit_mode'     => 'builder',
							'_elementor_template_type' => 'section',
						),
					)
				);
			}

			wp_send_json_success( $template );
		}

		/**
		 * Register AJAX actions
		 *
		 * Add new actions to handle data after an AJAX requests returned.
		 *
		 * @since 3.6.0
		 * @access public
		 */
		public function register_ajax_actions( $ajax_manager ) {

			if ( ! isset( $_REQUEST['actions'] ) ) {
				return;
			}

			$actions = json_decode( wp_unslash( $_REQUEST['actions'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

			$data = false;

			foreach ( $actions as $id => $action_data ) {
				if ( ! isset( $action_data['get_template_data'] ) ) {
					$data = $action_data;
				}
			}

			if ( ! $data ) {
				return;
			}

			if ( ! isset( $data['data'] ) ) {
				return;
			}

			if ( ! isset( $data['data']['source'] ) ) {
				return;
			}

			$source = $data['data']['source'];

			if ( 'premium-api' !== $source ) {
				return;
			}

			$ajax_manager->register_ajax_action(
				'get_template_data',
				function ( $data ) {
					return $this->get_template_data_array( $data );
				}
			);
		}

		/**
		 * Get template data array
		 *
		 * triggered to get an array for a single template data
		 *
		 * @since 3.6.0
		 * @access public
		 */
		public function get_template_data_array( $data ) {

			if ( ! current_user_can( 'edit_posts' ) ) {
				return false;
			}

			if ( empty( $data['template_id'] ) ) {
				return false;
			}

			$source_name = isset( $data['source'] ) ? esc_attr( $data['source'] ) : '';

			if ( ! $source_name ) {
				return false;
			}

			$source = isset( $this->sources[ $source_name ] ) ? $this->sources[ $source_name ] : false;

			if ( ! $source ) {
				return false;
			}

			if ( empty( $data['tab'] ) ) {
				return false;
			}

			$insert_media = isset( $data['withMedia'] ) ? $data['withMedia'] : true;

			$template = $source->get_item( $data['template_id'], $data['tab'], $insert_media );

			return $template;
		}

		/**
		 * Returns the instance.
		 *
		 * @since  3.6.0
		 * @return object
		 */
		public static function get_instance() {

			// If the single instance hasn't been set, set it now.
			if ( null == self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}
	}

}
