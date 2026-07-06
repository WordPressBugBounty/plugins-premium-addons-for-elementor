<?php
/**
 * MCP Settings.
 *
 * Admin controller backing the MCP Configuration tab. Reports whether WordPress
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
	 * Called once at the top of the MCP Configuration template. The pasted value
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

			$existing = $this->validate_existing_password();

			if ( is_wp_error( $existing ) ) {
				$result['existing_error'] = $existing;
			} else {
				$result['existing_password'] = $existing;
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
	 * authenticated with a WordPress application password — so the connect prompt
	 * is identical across them; this is just the ordered label list used to render
	 * the client tabs. Keep it in sync with the Compatible AI clients list in the
	 * product vision.
	 *
	 * @since 4.11.74
	 *
	 * @return array<string,string> Map of client key => display label.
	 */
	public static function get_supported_clients() {

		return array(
			'claude-code'    => 'Claude Code',
			'claude-desktop' => 'Claude Desktop',
			'codex'          => 'Codex',
			'antigravity'    => 'Antigravity',
			'cursor'         => 'Cursor',
			'vs-code'        => 'VS Code',
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
