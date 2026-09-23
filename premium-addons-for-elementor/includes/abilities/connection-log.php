<?php
/**
 * MCP Connection Log.
 *
 * Records per user that an AI client completed an MCP handshake with this site,
 * and reports the current connection state to the dashboard.
 */

namespace PremiumAddons\Includes\Abilities;

// Block direct access to the file.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Class Connection_Log.
 *
 * @since 4.11.74
 */
class Connection_Log {

	/**
	 * User meta holding the time that user last completed a handshake.
	 *
	 * @var string
	 */
	const META_KEY = 'pa_mcp_connection';

	/**
	 * Server ID registered in Bootstrap::register_server().
	 *
	 * @var string
	 */
	const SERVER_ID = 'premium-addons';

	/**
	 * Minimum seconds between two writes.
	 *
	 * @var int
	 */
	const WRITE_INTERVAL = 300;

	/**
	 * User meta the MCP adapter stores its live sessions in.
	 *
	 * @var string
	 */
	const SESSION_META_KEY = 'mcp_adapter_sessions';

	/**
	 * A client is talking to the site right now.
	 *
	 * @var string
	 */
	const STATE_ACTIVE = 'active';

	/**
	 * A client completed a handshake before, but has no session open now.
	 *
	 * @var string
	 */
	const STATE_CONNECTED = 'connected';

	/**
	 * No client ever connected as this user.
	 *
	 * @var string
	 */
	const STATE_NONE = 'none';

	/**
	 * Hook the record and forget listeners.
	 *
	 * @return void
	 */
	public static function init() {

		// Record the handshake, and clear it when the user's last password is deleted.
		add_filter( 'mcp_adapter_initialize_response', array( __CLASS__, 'record' ), 10, 2 );
		add_action( 'wp_delete_application_password', array( __CLASS__, 'forget' ) );
	}

	/**
	 * Record a completed MCP handshake.
	 *
	 * @param mixed                  $result Initialize result, passed through untouched.
	 * @param \WP\MCP\Core\McpServer $mcp    Server that handled the request.
	 * @return mixed Unmodified initialize result.
	 */
	public static function record( $result, $mcp ) {

		if ( self::SERVER_ID !== $mcp->get_server_id() ) {
			return $result;
		}

		$user_id = get_current_user_id();
		$now     = time();

		// Throttle repeated handshakes.
		if ( $now - self::get_user_connection( $user_id ) >= self::WRITE_INTERVAL ) {
			update_user_meta( $user_id, self::META_KEY, $now );
		}

		return $result;
	}

	/**
	 * Drop the connection record of a user who has no Premium Addons credential left.
	 *
	 * @param int $user_id User the password was deleted from.
	 * @return void
	 */
	public static function forget( $user_id ) {

		if ( self::has_own_credential( $user_id ) ) {
			return;
		}

		delete_user_meta( $user_id, self::META_KEY );
	}

	/**
	 * Whether a user holds something a Premium Addons MCP client could
	 * authenticate with: an application password not created by another MCP
	 * plugin, or a live OAuth token. An "Elementor MCP - …" or "Novamira…"
	 * password proves a connection elsewhere, not here.
	 *
	 * @since 4.11.107
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function has_own_credential( $user_id ) {

		foreach ( \WP_Application_Passwords::get_user_application_passwords( $user_id ) as $password ) {
			if ( '' === Route_Detector::password_route( $password['name'] ) ) {
				return true;
			}
		}

		return self::has_oauth_token( $user_id );
	}

	/**
	 * Whether a user holds a live OAuth access or refresh token. Guarded on the
	 * autoloaded opt-in flag so sites that never enabled OAuth (and have no
	 * token table) never query it.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function has_oauth_token( $user_id ) {

		if ( ! get_option( OAuth\Bootstrap::OPTION_ENABLED ) ) {
			return false;
		}

		return OAuth\Store::user_has_live_token( $user_id );
	}

	/**
	 * Describe how this site currently stands with AI clients.
	 *
	 * Answered for the viewing user only: another administrator who never
	 * connected sees the setup steps, not someone else's connection. A user
	 * with no Premium Addons credential is back where a new user starts no
	 * matter what was recorded earlier.
	 *
	 * The handshake record is required before live sessions count: the
	 * adapter stores sessions per user with no server id, so a session opened
	 * through Elementor MCP looks identical here.
	 *
	 * @return array {
	 *     @type string $state One of the STATE_* constants.
	 *     @type int    $time  Last activity for active, handshake time otherwise.
	 *     @type int    $count Live sessions, 0 when none are open.
	 * }
	 */
	public static function get_state() {

		$none = array(
			'state' => self::STATE_NONE,
			'time'  => 0,
			'count' => 0,
		);

		$user_id   = get_current_user_id();
		$connected = self::get_user_connection( $user_id );

		if ( ! $connected || ! self::has_own_credential( $user_id ) ) {
			return $none;
		}

		$sessions = self::get_active_sessions( $user_id );

		if ( null !== $sessions ) {
			return array(
				'state' => self::STATE_ACTIVE,
				'time'  => $sessions['last_activity'],
				'count' => $sessions['count'],
			);
		}

		return array(
			'state' => self::STATE_CONNECTED,
			'time'  => $connected,
			'count' => 0,
		);
	}

	/**
	 * Whether the viewing user has a Premium Addons MCP connection. The
	 * dashboard shows two states only; the active/connected split stays internal.
	 *
	 * @since 4.11.107
	 *
	 * @return bool
	 */
	public static function is_connected() {
		return self::STATE_NONE !== self::get_state()['state'];
	}

	/**
	 * Get the time one user last completed a handshake.
	 *
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return int Timestamp, or 0 when that user never connected.
	 */
	public static function get_user_connection( $user_id = 0 ) {

		$user_id = $user_id ? $user_id : get_current_user_id();

		return (int) get_user_meta( $user_id, self::META_KEY, true );
	}

	/**
	 * Get the sessions a user currently has open with the MCP adapter.
	 *
	 * Expired sessions are only pruned on that user's next MCP request, so they
	 * are filtered out here as well. This answers "connected right now", while
	 * the record above answers "connected at least once".
	 *
	 * @param int $user_id User ID. Defaults to the current user.
	 * @return array|null {
	 *     Session summary, or null when the user has no live session.
	 *
	 *     @type int $count         Number of live sessions.
	 *     @type int $last_activity Timestamp of the most recent request.
	 * }
	 */
	public static function get_active_sessions( $user_id = 0 ) {

		$user_id  = $user_id ? $user_id : get_current_user_id();
		$sessions = get_user_meta( $user_id, self::SESSION_META_KEY, true );

		if ( ! is_array( $sessions ) ) {
			return null;
		}

		/** This filter is documented in includes/abilities/vendor/wordpress/mcp-adapter/includes/Transport/Infrastructure/SessionManager.php */
		$timeout = (int) apply_filters( 'mcp_adapter_session_inactivity_timeout', DAY_IN_SECONDS );
		$now     = time();
		$summary = array(
			'count'         => 0,
			'last_activity' => 0,
		);

		foreach ( $sessions as $session ) {

			if ( ! isset( $session['last_activity'] ) || $session['last_activity'] + $timeout < $now ) {
				continue;
			}

			++$summary['count'];
			$summary['last_activity'] = max( $summary['last_activity'], $session['last_activity'] );
		}

		return $summary['count'] ? $summary : null;
	}
}
