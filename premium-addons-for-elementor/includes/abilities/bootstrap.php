<?php
/**
 * AI Abilities Bootstrap.
 *
 * Stands up the bundled MCP server that exposes Premium Addons widgets,
 * templates and site data as MCP tools for AI agents.
 */

namespace PremiumAddons\Includes\Abilities;

use PremiumAddons\Includes\Abilities\Registry\Ability_Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Class Bootstrap.
 *
 * @since 4.11.74
 */
class Bootstrap {

	/**
	 * Class instance.
	 *
	 * @var Bootstrap|null
	 */
	private static $instance = null;

	/**
	 * Ability handler registry.
	 *
	 * @var Ability_Registry
	 */
	private $registry;

	/**
	 * Get class instance.
	 *
	 * @return Bootstrap
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Bootstrap constructor.
	 */
	public function __construct() {

		$this->registry = new Ability_Registry();

		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		// Register PA abilities with core as soon as the Abilities API boots.
		// These listeners are cheap and must be in place before anything first
		// accesses the abilities registry, so they stay on the early path.
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );

		// The MCP adapter is only needed while serving a REST request (or WP-CLI),
		// so defer its vendor autoloader and class graph to that point instead of
		// loading them on every front-end and admin page.
		add_action( 'rest_api_init', array( $this, 'boot_mcp_adapter' ), 5 );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			add_action( 'init', array( $this, 'boot_mcp_adapter' ), 5 );
		}
	}

	/**
	 * Boot the MCP adapter and hook server registration.
	 *
	 * Deferred to rest_api_init / WP-CLI so the vendor autoloader and adapter
	 * class graph never load on ordinary front-end or admin requests.
	 *
	 * @return void
	 */
	public function boot_mcp_adapter() {

		require_once PREMIUM_ADDONS_PATH . 'includes/abilities/vendor/autoload_packages.php';

		if ( ! class_exists( \WP\MCP\Core\McpAdapter::class ) ) {
			return;
		}

		\WP\MCP\Core\McpAdapter::instance();

		add_action( 'mcp_adapter_init', array( $this, 'register_server' ), 20 );
	}

	/**
	 * Get category definitions.
	 *
	 * @return array
	 */
	public static function get_categories() {
		return array(
			'pa-discovery'            => array(
				'label'       => __( 'Discovery', 'premium-addons-for-elementor' ),
				'description' => __( 'Abilities that read site and content state without changing anything.', 'premium-addons-for-elementor' ),
			),
			'pa-page-post-management' => array(
				'label'       => __( 'Page/Post Management', 'premium-addons-for-elementor' ),
				'description' => __( 'Abilities that create and manage WordPress pages and posts as Elementor documents.', 'premium-addons-for-elementor' ),
			),
			'pa-build'                => array(
				'label'       => __( 'Build', 'premium-addons-for-elementor' ),
				'description' => __( 'Abilities that create, edit, and remove Elementor elements on a page — containers, atomic flexbox, element settings, and deletion.', 'premium-addons-for-elementor' ),
			),
			'pa-dashboard'            => array(
				'label'       => __( 'Dashboard', 'premium-addons-for-elementor' ),
				'description' => __( 'Abilities for managing the Premium Addons dashboard.', 'premium-addons-for-elementor' ),
			),
		);
	}

	/**
	 * Register ability categories.
	 *
	 * @return void
	 */
	public function register_categories() {
		foreach ( self::get_categories() as $slug => $category ) {
			wp_register_ability_category( $slug, $category );
		}
	}

	/**
	 * Register enabled abilities.
	 *
	 * @return void
	 */
	public function register_abilities() {
		$this->load_abilities_classes();
		$this->registry->register_enabled_abilities();
	}

	/**
	 * Register the Premium Addons MCP server.
	 *
	 * @param \WP\MCP\Core\McpAdapter $mcp_adapter MCP adapter instance.
	 * @return void
	 */
	public function register_server( $mcp_adapter ) {
		if ( ! did_action( 'init' ) ) {
			return;
		}

		$this->load_abilities_classes();

		$tools = array_values(
			array_intersect(
				$this->get_registered_ability_names(),
				$this->registry->get_enabled_names()
			)
		);

		$mcp_adapter->create_server(
			'premium-addons',
			'premium-addons',
			'mcp',
			__( 'Premium Addons MCP Server', 'premium-addons-for-elementor' ),
			__( 'Exposes Premium Addons widgets, templates and site data as MCP tools for AI agents.', 'premium-addons-for-elementor' ),
			'v' . PREMIUM_ADDONS_VERSION,
			array( \WP\MCP\Transport\HttpTransport::class ),
			\WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler::class,
			\WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler::class,
			$tools,
			array(),
			array(),
			null
		);
	}

	/**
	 * Get the cached ability catalog for the settings UI.
	 *
	 * @return array
	 */
	public function get_abilities_catalog() {
		$hash               = md5( implode( ',', $this->ability_classes() ) . '|' . PREMIUM_ADDONS_VERSION );
		$catalog            = get_transient( 'pa_ai_abilities_catalog' );
		$is_current_catalog = is_array( $catalog )
			&& isset( $catalog['hash'], $catalog['items'] )
			&& is_array( $catalog['items'] )
			&& $hash === $catalog['hash'];

		if ( $is_current_catalog ) {
			return $catalog['items'];
		}

		$this->load_abilities_classes();

		$ability_items = $this->registry->get_abilities_meta();

		set_transient(
			'pa_ai_abilities_catalog',
			array(
				'hash'  => $hash,
				'items' => $ability_items,
			)
		);

		return $ability_items;
	}

	/**
	 * Get handler class names.
	 *
	 * @return array
	 */
	private function ability_classes() {
		return array(
			Discovery\Check_Elementor_Element::class,
			Discovery\Detect_Atomic_Support::class,
			Discovery\Get_Addon_Schema::class,
			Discovery\Get_Element_Settings::class,
			Discovery\Get_Global_Settings::class,
			Discovery\Get_Id_By_Title::class,
			Discovery\Get_Page_Structure::class,
			Discovery\Get_Widget_Schema::class,
			Discovery\List_Available_Elements::class,
			Discovery\List_Pa_Addons::class,
			Discovery\List_Pages::class,
			Discovery\List_Templates::class,
			Build\Add_Container::class,
			Build\Add_Flexbox::class,
			Build\Insert_Widget::class,
			Build\Remove_Element::class,
			Build\Update_Element_Settings::class,
			Dashboard\Clear_Dynamic_Assets::class,
			Dashboard\Disable_Unused_Widgets::class,
			Dashboard\Get_Settings::class,
			Dashboard\Scan_Usage::class,
			Dashboard\Subscribe_Newsletter::class,
			Dashboard\Update_Setting::class,
			PagePostManagement\Change_Post_Status::class,
			PagePostManagement\Create_Elementor_Template::class,
			PagePostManagement\Create_Page::class,
			PagePostManagement\Duplicate_Post::class,
		);
	}

	/**
	 * Populate the handler registry once.
	 *
	 * @return void
	 */
	private function load_abilities_classes() {

		if ( ! empty( $this->registry->get_abilities_names() ) ) {
			return;
		}

		foreach ( $this->ability_classes() as $ability_class ) {
			$this->registry->register( new $ability_class() );
		}

		do_action( 'pa_abilities_register_handlers', $this->registry );
	}

	/**
	 * Get registered Premium Addons ability names from WordPress.
	 *
	 * @return array
	 */
	private function get_registered_ability_names() {
		$names = array();

		foreach ( wp_get_abilities() as $ability ) {
			$name = $ability->get_name();

			if ( 0 === strpos( $name, Ability_Registry::PREFIX ) ) {
				$names[] = $name;
			}
		}

		return $names;
	}
}
