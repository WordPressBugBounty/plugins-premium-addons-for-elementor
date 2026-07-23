<?php
/**
 * MCP Settings.
 *
 * Admin controller backing the Configure MCP Server step. Reports whether WordPress
 * Application Passwords are available, validates the password the user pastes in
 * (created on their profile page) and lists the AI clients shown in the connect
 * step. The dashboard tabs themselves are registered in
 * Admin_Helper::set_admin_tabs().
 */

namespace PremiumAddons\Admin\Includes;

// Block direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Class MCP_Settings.
 *
 * @since 4.11.74
 */
class MCP_Settings {

	/**
	 * Default local name for the MCP server.
	 *
	 * @var string
	 */
	const DEFAULT_SERVER_NAME = 'premium-addons';

	/**
	 * Placeholder replaced by the editable client alias in the admin UI.
	 *
	 * @var string
	 */
	const NAME_TOKEN = '%%PA_NAME%%';

	/**
	 * Pinned mcp-remote version used by bridge clients.
	 *
	 * @var string
	 */
	const MCP_REMOTE_VERSION = '0.1.38';

	/**
	 * Class instance.
	 *
	 * @var instance
	 */
	private static $instance = null;

	/**
	 * Whether WordPress Application Passwords can be generated on this site.
	 *
	 * @return array {
	 *     @type bool   $available Whether a password can be generated now.
	 *     @type string $reason    One of available|unsupported|filtered.
	 *     @type string $message   User-facing explanation when unavailable.
	 * }
	 */
	public static function app_passwords_status() {

		if ( wp_is_application_passwords_available() ) {
			return array(
				'available' => true,
				'reason'    => 'available',
				'message'   => '',
			);
		}

		if ( ! wp_is_application_passwords_supported() ) {
			return array(
				'available' => false,
				'reason'    => 'unsupported',
				'message'   => __( 'Application Passwords require HTTPS, or WP_ENVIRONMENT_TYPE set to "local" on local sites.', 'premium-addons-for-elementor' ),
			);
		}

		return array(
			'available' => false,
			'reason'    => 'filtered',
			'message'   => __( 'Application Passwords are disabled on this site, likely by a security plugin. Re-enable them to connect an AI client.', 'premium-addons-for-elementor' ),
		);
	}

	/**
	 * Process the use-existing password submission.
	 *
	 * Called once at the top of the MCP config template. The pasted value
	 * is only echoed back into the connection details, never stored on the site.
	 *
	 * @return array {
	 *     @type string|null    $existing_password Plaintext value pasted by the user.
	 *     @type \WP_Error|null  $existing_error    Validation error for the pasted value.
	 * }
	 */
	public function maybe_handle_password_forms() {

		$result = array(
			'existing_password' => null,
			'existing_error'    => null,
		);

		if ( ! current_user_can( 'manage_options' ) ) {
			return $result;
		}

		if ( isset( $_POST['pa_mcp_use_existing_password'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce is verified in validate_existing_password().

			$is_existing = $this->validate_existing_password();

			if ( is_wp_error( $is_existing ) ) {
				$result['existing_error'] = $is_existing;
			} else {
				$result['existing_password'] = $is_existing;
			}
		}

		return $result;
	}

	/**
	 * Validate an application password pasted by the user.
	 *
	 * The value is only echoed back into the connection details — it is never
	 * stored on the site.
	 *
	 * @return string|\WP_Error Trimmed value on success, WP_Error otherwise.
	 */
	private function validate_existing_password() {

		check_admin_referer( 'pa_mcp_use_existing_password' );

		$value = isset( $_POST['pa_mcp_existing_password'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['pa_mcp_existing_password'] ) ) ) : '';

		if ( '' === $value ) {
			return new \WP_Error( 'empty', __( 'Paste the application password value before submitting.', 'premium-addons-for-elementor' ) );
		}

		if ( strlen( $value ) < 16 ) {
			return new \WP_Error( 'too_short', __( 'That does not look like an application password. WordPress application passwords are at least 16 characters long.', 'premium-addons-for-elementor' ) );
		}

		return $value;
	}

	/**
	 * Supported AI clients shown in the "Connect Your AI Client" section.
	 *
	 * Every client connects the same way — a streamable-HTTP MCP server
	 * authenticated with a WordPress application password. The array order is the
	 * tab display order: the clients that ship a copy-paste config snippet (the
	 * keys in client_shape_map()) come first, then the prompt-only clients. Keep
	 * it in sync with the Compatible AI clients list in the product vision.
	 *
	 * @since 4.11.74
	 *
	 * @return array<string,string> Map of client key => display label.
	 */
	public static function get_supported_clients() {

		return array(
			'claude-code'    => 'Claude Code',
			'claude-desktop' => 'Claude Desktop',
			'cursor'         => 'Cursor',
			'vs-code'        => 'VS Code',
			'codex'          => 'Codex',
			'antigravity'    => 'Antigravity',
			'github-copilot' => 'GitHub Copilot',
			'windsurf'       => 'Windsurf',
			'cline'          => 'Cline',
			'gemini-cli'     => 'Gemini CLI',
			'roo-code'       => 'Roo Code',
			'amazon-q'       => 'Amazon Q',
			'zed'            => 'Zed',
			'kilo-code'      => 'Kilo Code',
			'opencode'       => 'OpenCode',
		);
	}

	/**
	 * Build connection details for every supported AI client.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $username     WordPress username.
	 * @param string $password     WordPress application password.
	 * @param string $alias        Local client alias.
	 * @return array<string,array<string,string|null>> Client configuration map.
	 */
	public function build_client_configs( $endpoint_url, $username, $password, $alias = self::DEFAULT_SERVER_NAME ) {

		$clients            = self::get_supported_clients();
		$shape_map          = self::client_shape_map();
		$auth_token         = self::basic_auth_token( $username, $password );
		$clean_alias        = (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $alias );
		$deeplink_alias     = '' !== $clean_alias ? $clean_alias : self::DEFAULT_SERVER_NAME;
		$connection_context = array(
			'endpoint_url'   => $endpoint_url,
			'auth_token'     => $auth_token,
			'deeplink_alias' => $deeplink_alias,
		);
		$configs            = array();

		foreach ( $clients as $client_key => $client_label ) {
			$client_shape = isset( $shape_map[ $client_key ] ) ? $shape_map[ $client_key ] : null;

			$configs[ $client_key ] = $this->build_client_config( $client_key, $client_label, $client_shape, $connection_context );
		}

		return $configs;
	}

	/**
	 * Build one supported client's configuration entry.
	 *
	 * @param string                    $client_key   Client key.
	 * @param string                    $client_label Client display label.
	 * @param array<string,string>|null $client_shape First-class client shape, or null.
	 * @param array<string,string>      $connection_context Endpoint and authentication context.
	 * @return array<string,string|null> Client configuration entry.
	 */
	private function build_client_config( $client_key, $client_label, $client_shape, $connection_context ) {

		$code     = null;
		$deeplink = null;

		if ( null !== $client_shape ) {
			$code = $this->build_client_snippet( $client_shape, $connection_context['endpoint_url'], $connection_context['auth_token'] );

			if ( 'cursor' === $client_key ) {
				$deeplink = $this->build_cursor_deeplink( $connection_context['deeplink_alias'], $connection_context['endpoint_url'], $connection_context['auth_token'] );
			}
		}

		return array(
			'label'    => $client_label,
			'shape'    => null !== $client_shape ? $client_shape['shape'] : null,
			'lang'     => null !== $client_shape ? $client_shape['lang'] : null,
			'hint'     => null !== $client_shape ? $client_shape['hint'] : null,
			'code'     => $code,
			'deeplink' => $deeplink,
			'prompt'   => $this->build_agent_prompt( $client_label, $connection_context['endpoint_url'], $connection_context['auth_token'] ),
		);
	}

	/**
	 * Get the pinned configuration shape for first-class clients.
	 *
	 * @return array<string,array<string,string>> First-class client shape map.
	 */
	private static function client_shape_map() {

		return array(
			'claude-code'    => array(
				'shape'   => 'shell',
				'variant' => 'claude-mcp-add',
				'lang'    => 'shell',
				'hint'    => __( 'Run this command in your terminal (no config file).', 'premium-addons-for-elementor' ),
			),
			'claude-desktop' => array(
				'shape'   => 'bridge',
				'variant' => 'mcp-remote',
				'lang'    => 'json',
				'hint'    => __( 'Configuration file: claude_desktop_config.json', 'premium-addons-for-elementor' ),
			),
			'cursor'         => array(
				'shape'   => 'native',
				'variant' => 'mcpServers',
				'lang'    => 'json',
				'hint'    => __( 'Configuration file: ~/.cursor/mcp.json (or project .cursor/mcp.json)', 'premium-addons-for-elementor' ),
			),
			'vs-code'        => array(
				'shape'   => 'native',
				'variant' => 'servers',
				'lang'    => 'json',
				'hint'    => __( 'Configuration file: .vscode/mcp.json', 'premium-addons-for-elementor' ),
			),
			'codex'          => array(
				'shape'   => 'toml',
				'variant' => 'mcp_servers',
				'lang'    => 'toml',
				'hint'    => __( 'Configuration file: ~/.codex/config.toml', 'premium-addons-for-elementor' ),
			),
		);
	}

	/**
	 * Build an HTTP Basic authentication token.
	 *
	 * @param string $username WordPress username.
	 * @param string $password WordPress application password.
	 * @return string Base64-encoded HTTP Basic token.
	 */
	private static function basic_auth_token( $username, $password ) {

		$password = str_replace( ' ', '', $password );

		return base64_encode( $username . ':' . $password );
	}

	/**
	 * Dispatch a first-class client configuration to its format builder.
	 *
	 * @param array<string,string> $client_shape Client shape definition.
	 * @param string               $endpoint_url MCP endpoint URL.
	 * @param string               $auth_token   Base64-encoded HTTP Basic token.
	 * @return string Client configuration snippet.
	 */
	private function build_client_snippet( $client_shape, $endpoint_url, $auth_token ) {

		switch ( $client_shape['shape'] ) {
			case 'shell':
				return $this->build_shell_snippet( $endpoint_url, $auth_token );
			case 'native':
				return $this->build_native_json_snippet( $client_shape['variant'], $endpoint_url, $auth_token );
			case 'bridge':
				return $this->build_bridge_snippet( $endpoint_url, $auth_token, self::MCP_REMOTE_VERSION );
			case 'toml':
				return $this->build_toml_snippet( $endpoint_url, $auth_token );
		}

		throw new \InvalidArgumentException( 'Unsupported MCP client shape.' );
	}

	/**
	 * Build the Claude Code shell command.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $auth_token   Base64-encoded HTTP Basic token.
	 * @return string Shell command.
	 */
	private function build_shell_snippet( $endpoint_url, $auth_token ) {

		return sprintf(
			'claude mcp add --transport http %1$s "%2$s" --header "Authorization: Basic %3$s"',
			self::NAME_TOKEN,
			$endpoint_url,
			$auth_token
		);
	}

	/**
	 * Build a native remote-HTTP JSON configuration.
	 *
	 * @param string $variant      Native JSON variant.
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $auth_token   Base64-encoded HTTP Basic token.
	 * @return string JSON configuration.
	 */
	private function build_native_json_snippet( $variant, $endpoint_url, $auth_token ) {

		$server = array(
			'url'     => $endpoint_url,
			'headers' => array(
				'Authorization' => 'Basic ' . $auth_token,
			),
		);

		if ( 'servers' === $variant ) {
			$server = array_merge( array( 'type' => 'http' ), $server );
		}

		$config = array(
			$variant => array(
				self::NAME_TOKEN => $server,
			),
		);

		return (string) wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Build a Claude Desktop mcp-remote bridge configuration.
	 *
	 * @param string $endpoint_url         MCP endpoint URL.
	 * @param string $auth_token           Base64-encoded HTTP Basic token.
	 * @param string $pinned_remote_version Pinned mcp-remote package version.
	 * @return string JSON configuration.
	 */
	private function build_bridge_snippet( $endpoint_url, $auth_token, $pinned_remote_version ) {

		$config = array(
			'mcpServers' => array(
				self::NAME_TOKEN => array(
					'command' => 'npx',
					'args'    => array(
						'-y',
						'mcp-remote@' . $pinned_remote_version,
						$endpoint_url,
						'--header',
						'Authorization: Basic ' . $auth_token,
					),
				),
			),
		);

		return (string) wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
	}

	/**
	 * Build a native Codex Streamable HTTP configuration.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $auth_token   Base64-encoded HTTP Basic token.
	 * @return string TOML configuration.
	 */
	private function build_toml_snippet( $endpoint_url, $auth_token ) {

		$endpoint_url = str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), $endpoint_url );
		$auth_header  = str_replace( array( '\\', '"' ), array( '\\\\', '\\"' ), 'Basic ' . $auth_token );

		return sprintf(
			"[mcp_servers.%1\$s]\nurl = \"%2\$s\"\nhttp_headers = { \"Authorization\" = \"%3\$s\" }",
			self::NAME_TOKEN,
			$endpoint_url,
			$auth_header
		);
	}

	/**
	 * Build a one-click Cursor install URL.
	 *
	 * @param string $alias        Local client alias.
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $auth_token   Base64-encoded HTTP Basic token.
	 * @return string Cursor deeplink.
	 */
	private function build_cursor_deeplink( $alias, $endpoint_url, $auth_token ) {

		$config = array(
			'url'     => $endpoint_url,
			'headers' => array(
				'Authorization' => 'Basic ' . $auth_token,
			),
		);
		$json   = (string) wp_json_encode( $config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );

		return 'cursor://anysphere.cursor-deeplink/mcp/install?name=' . rawurlencode( $alias ) . '&config=' . rawurlencode( base64_encode( $json ) );
	}

	/**
	 * Build the universal plain-English fallback prompt.
	 *
	 * @param string $client_label Client display label.
	 * @param string $endpoint_url MCP endpoint URL.
	 * @param string $auth_token   Base64-encoded HTTP Basic token.
	 * @return string Agent prompt.
	 */
	private function build_agent_prompt( $client_label, $endpoint_url, $auth_token ) {

		return sprintf(
			/* translators: 1: AI client name, 2: local server alias, 3: MCP endpoint URL, 4: HTTP Basic authorization value. */
			__(
				'Connect %1$s to my WordPress site by adding the MCP server below, then list its tools to confirm the connection works:

- Name: %2$s
- Transport: Streamable HTTP
- URL: %3$s
- Auth: HTTP Basic
- Authorization header: %4$s',
				'premium-addons-for-elementor'
			),
			$client_label,
			self::NAME_TOKEN,
			$endpoint_url,
			'Basic ' . $auth_token
		);
	}

	/**
	 * Get the host used by the actual MCP endpoint.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @return string Endpoint host.
	 */
	public static function get_endpoint_host( $endpoint_url ) {

		$host = wp_parse_url( $endpoint_url, PHP_URL_HOST );

		if ( ! is_string( $host ) || '' === $host ) {
			$host = wp_parse_url( home_url(), PHP_URL_HOST );
		}

		return is_string( $host ) ? strtolower( trim( $host, '[]' ) ) : '';
	}

	/**
	 * Get the scheme used by the actual MCP endpoint.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @return string http or https.
	 */
	public static function endpoint_scheme( $endpoint_url ) {

		$scheme = strtolower( (string) wp_parse_url( $endpoint_url, PHP_URL_SCHEME ) );

		return 'https' === $scheme ? 'https' : 'http';
	}

	/**
	 * Determine whether a host is local or private.
	 *
	 * @param string $host Endpoint host.
	 * @return bool Whether the host is local.
	 */
	public static function is_local_host( $host ) {

		$host     = strtolower( trim( $host, '[]' ) );
		$suffixes = array( 'local', 'test', 'localhost', 'ddev.site', 'lndo.site' );

		foreach ( $suffixes as $suffix ) {
			if ( $host === $suffix || substr( $host, -strlen( '.' . $suffix ) ) === '.' . $suffix ) {
				return true;
			}
		}

		if ( '::1' === $host || 0 === strpos( $host, '127.' ) || 0 === strpos( $host, '10.' ) || 0 === strpos( $host, '192.168.' ) ) {
			return true;
		}

		return 1 === preg_match( '/^172\.(1[6-9]|2[0-9]|3[01])\./', $host );
	}

	/**
	 * Determine whether an endpoint looks like a production connection.
	 *
	 * @param string $endpoint_url MCP endpoint URL.
	 * @return bool Whether to show the production advisory.
	 */
	public static function looks_like_production( $endpoint_url ) {

		$environment = wp_get_environment_type();

		return ! self::is_local_host( self::get_endpoint_host( $endpoint_url ) )
			&& ! in_array( $environment, array( 'local', 'development' ), true );
	}

	/**
	 * Creates and returns an instance of the class.
	 *
	 * @return MCP_Settings
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;
	}
}
