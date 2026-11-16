<?php
/**
 * Returning User Condition Handler.
 */

namespace PremiumAddons\Includes\PA_Display_Conditions\Conditions;

// Elementor Classes.
use Elementor\Controls_Manager;

// PA Classes.
use PremiumAddons\Includes\Helper_Functions;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Return_Visitor
 */
class Return_Visitor extends Condition {

	const COOKIE_NAME = 'PaVisitorData';

	const CONSENT_CATEGORY = 'preferences';

	/**
	 * Get Controls Options.
	 *
	 * @access public
	 * @since 4.9.21
	 *
	 * @return array|void  controls options
	 */
	public function get_control_options() {

		return array(
			'label'       => __( 'Value', 'premium-addons-for-elementor' ),
			'type'        => Controls_Manager::SELECT,
			'options'     => array(
				'return' => __( 'Returning User', 'premium-addons-for-elementor' ),
			),
			'default'     => 'return',
			'label_block' => true,
			'condition'   => array(
				'pa_condition_key' => 'return_visitor',
			),
		);
	}

	/**
	 * Compare Condition Value.
	 *
	 * @access public
	 * @since 4.9.21
	 *
	 * @param array       $settings      element settings.
	 * @param string      $operator      condition operator.
	 * @param string      $value         condition value.
	 * @param string      $compare_val   condition value.
	 * @param string|bool $tz        time zone.

	 * @return bool|void
	 */
	public function compare_value( $settings, $operator, $value, $compare_val, $tz ) {

		// First page load is new; the cookie marks every later load as returning.
		$condition_result = isset( $_COOKIE[ self::COOKIE_NAME ] ) && self::has_consent();

		if ( ! $condition_result ) {

			wp_add_inline_script(
				'elementor-frontend',
				'jQuery( window ).on( "elementor/frontend/init", function() {

					var consentCategory = "' . self::CONSENT_CATEGORY . '";

					function setVisitorCookie() {

						var paSecure = ( document.location.protocol === "https:" ) ? "secure" : "";
						var visitDate = new Date( new Date().getTime() + 1000 * 86400 * 365 ).toGMTString();

						document.cookie = "' . self::COOKIE_NAME . '=enabled;expires=" + visitDate + ";SameSite=Strict;" + paSecure;
					}

					function setVisitorCookieIfConsented() {

						if ( "function" !== typeof wp_has_consent || wp_has_consent( consentCategory ) ) {
							setVisitorCookie();
						}
					}

					if ( window.waitfor_consent_hook && "undefined" === typeof window.wp_consent_type ) {
						document.addEventListener( "wp_consent_type_defined", setVisitorCookieIfConsented );
					} else {
						setVisitorCookieIfConsented();
					}

					document.addEventListener( "wp_listen_for_consent_change", function( e ) {

						if ( "allow" === e.detail[ consentCategory ] ) {
							setVisitorCookie();
						}
					} );

				}); '
			);

		}

		return Helper_Functions::get_final_result( $condition_result, $operator );
	}

	/**
	 * Whether the visitor allows the cookie; always true without a WP Consent API plugin.
	 *
	 * @access private
	 * @since 4.11.120
	 *
	 * @return bool
	 */
	private static function has_consent() {

		return ! function_exists( 'wp_has_consent' ) || wp_has_consent( self::CONSENT_CATEGORY );
	}

	/**
	 * Lists the cookie in WP Consent API so consent plugins can show and clear it.
	 *
	 * @access public
	 * @since 4.11.120
	 */
	public static function register_cookie_info() {

		wp_add_cookie_info(
			self::COOKIE_NAME,
			'Premium Addons for Elementor',
			self::CONSENT_CATEGORY,
			__( '1 year', 'premium-addons-for-elementor' ),
			__( 'Recognizes returning visitors for the Returning Visitor display condition.', 'premium-addons-for-elementor' )
		);
	}
}
