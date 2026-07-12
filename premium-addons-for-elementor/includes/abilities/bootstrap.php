<?php
/**
 * AI Abilities Bootstrap.
 *
 * Stands up the bundled MCP server that exposes Premium Addons widgets,
 * templates and site data as MCP tools for AI agents.
 */

namespace PremiumAddons\Includes\Abilities;

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
	 * Class instance
	 *
	 * @var instance
	 */
	private static $instance = null;

	/**
	 * Ability names exposed as the MCP server's tools.
	 *
	 * Must be known statically. register_server() reads this list on the
	 * mcp_adapter_init hook (rest_api_init priority 15), which runs *before*
	 * wp_abilities_api_init fires — so it cannot be populated from
	 * register_abilities(). The abilities are still registered on
	 * wp_abilities_api_init and resolved just-in-time when the server reads its
	 * tools (the adapter calls wp_get_ability(), which boots the registry).
	 *
	 * @var array
	 */
	private $ability_names = array(
		'premium-addons/get-id-by-title',
		'premium-addons/list-pages',
		'premium-addons/list-templates',
		'premium-addons/get-global-settings',
		'premium-addons/get-page-structure',
		'premium-addons/get-element-settings',
		'premium-addons/get-widget-schema',
		'premium-addons/detect-atomic-support',
		'premium-addons/check-elementor-element',
		'premium-addons/create-page',
		'premium-addons/create-elementor-template',
		'premium-addons/add-flexbox',
		'premium-addons/add-container',
		'premium-addons/update-element-settings',
		'premium-addons/insert-widget',
		'premium-addons/get-settings',
		'premium-addons/update-setting',
		'premium-addons/scan-usage',
		'premium-addons/disable-unused-widgets',
		'premium-addons/clear-dynamic-assets',
		'premium-addons/subscribe-newsletter',
	);

	/**
	 * Filesystem path to the abilities directory.
	 *
	 * @var string
	 */
	private $abilities_path;

	/**
	 * Get class instance.
	 *
	 * Instantiates the bootstrap (and the MCP server) once.
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
	 *
	 * Loads the bundled dependencies, registers the ability categories and
	 * abilities, and — only when the bundled MCP Adapter is present — stands up
	 * the Premium Addons MCP server.
	 */
	public function __construct() {

		$this->abilities_path = PREMIUM_ADDONS_PATH . 'includes/abilities/';

		require_once $this->abilities_path . 'vendor/autoload_packages.php';

		// The Abilities API is the only hard dependency; bail if it is unavailable.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		// Register categories then abilities unconditionally so they stay reachable
		// over REST at /wp-json/wp-abilities/v1/ even without the MCP Adapter.
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_categories' ) );

		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );

		// Stand up the MCP server only when the bundled adapter is present.
		if ( ! class_exists( \WP\MCP\Core\McpAdapter::class ) ) {
			return;
		}

		\WP\MCP\Core\McpAdapter::instance();

		add_action( 'mcp_adapter_init', array( $this, 'register_server' ) );
	}

	/**
	 * Register the Premium Addons MCP server.
	 *
	 * @param \WP\MCP\Core\McpAdapter $mcp_adapter MCP adapter instance.
	 */
	public function register_server( $mcp_adapter ) {

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
			$this->ability_names,
			array(),
			array(),
			null
		);
	}

	/**
	 * Register ability categories.
	 *
	 * Runs before any ability registers, since a category is a required argument
	 * of wp_register_ability and must reference an already-registered slug.
	 */
	public function register_categories() {

		wp_register_ability_category(
			'pa-discovery',
			array(
				'label'       => __( 'Discovery', 'premium-addons-for-elementor' ),
				'description' => __( 'Abilities that read site and content state without changing anything.', 'premium-addons-for-elementor' ),
			)
		);

		wp_register_ability_category(
			'pa-page-post-management',
			array(
				'label'       => __( 'Page/Post Management', 'premium-addons-for-elementor' ),
				'description' => __( 'Abilities that create and manage WordPress pages and posts as Elementor documents.', 'premium-addons-for-elementor' ),
			)
		);

		wp_register_ability_category(
			'pa-build',
			array(
				'label'       => __( 'Build', 'premium-addons-for-elementor' ),
				'description' => __( 'Abilities that create and edit Elementor elements on a page — containers, atomic flexbox, and element settings.', 'premium-addons-for-elementor' ),
			)
		);

		wp_register_ability_category(
			'pa-dashboard',
			array(
				'label'       => __( 'Dashboard', 'premium-addons-for-elementor' ),
				'description' => __( 'Abilities for managing the Premium Addons dashboard.', 'premium-addons-for-elementor' ),
			)
		);
	}

	/**
	 * Register abilities.
	 *
	 * Dispatcher that delegates to one registration method per ability group, so
	 * each group's files are loaded in isolation. Add a register_<group>_abilities()
	 * method and a call here when introducing a new group.
	 */
	public function register_abilities() {

		$this->register_discovery_abilities();
		$this->register_page_post_management_abilities();
		$this->register_build_abilities();
		$this->register_dashboard_abilities();
	}

	/**
	 * Register discovery abilities.
	 */
	public function register_discovery_abilities() {

		require_once $this->abilities_path . 'discovery/get-id-by-title.php';
		require_once $this->abilities_path . 'discovery/list-pages.php';
		require_once $this->abilities_path . 'discovery/list-templates.php';
		require_once $this->abilities_path . 'discovery/get-global-settings.php';
		require_once $this->abilities_path . 'discovery/get-page-structure.php';
		require_once $this->abilities_path . 'discovery/get-element-settings.php';
		require_once $this->abilities_path . 'discovery/get-widget-schema.php';
		require_once $this->abilities_path . 'discovery/detect-atomic-support.php';
		require_once $this->abilities_path . 'discovery/check-elementor-element.php';
	}

	/**
	 * Register build abilities.
	 *
	 * The shared Helpers class the ability callbacks use resolves through the
	 * plugin autoloader.
	 */
	public function register_build_abilities() {

		require_once $this->abilities_path . 'build/add-flexbox.php';
		require_once $this->abilities_path . 'build/add-container.php';
		require_once $this->abilities_path . 'build/update-element-settings.php';
		require_once $this->abilities_path . 'build/insert-widget.php';
	}

	/**
	 * Register page/post management abilities.
	 */
	public function register_page_post_management_abilities() {

		require_once $this->abilities_path . 'page-post-management/create-page.php';
		require_once $this->abilities_path . 'page-post-management/create-elementor-template.php';
	}

	/**
	 * Register dashboard abilities.
	 */
	public function register_dashboard_abilities() {

		require_once $this->abilities_path . 'dashboard/get-settings.php';
		require_once $this->abilities_path . 'dashboard/update-setting.php';
		require_once $this->abilities_path . 'dashboard/scan-usage.php';
		require_once $this->abilities_path . 'dashboard/disable-unused-widgets.php';
		require_once $this->abilities_path . 'dashboard/clear-dynamic-assets.php';
		require_once $this->abilities_path . 'dashboard/subscribe-newsletter.php';
	}
}
