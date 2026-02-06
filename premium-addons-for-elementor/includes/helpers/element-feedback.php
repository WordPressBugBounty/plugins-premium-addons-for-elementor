<?php
/**
 * Element Feedback
 *
 * Handles element feedback functionality.
 */

namespace PremiumAddons\Includes\Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class Element_Feedback.
 */
class Element_Feedback {


	/**
	 * Class instance
	 *
	 * @var instance
	 */
	private static $instance = null;

	public function __construct() {

		add_action( 'wp_ajax_pa_send_element_feedback', array( $this, 'send' ) );
	}

	public function send() {

		check_ajax_referer( 'pa-editor', 'security' );

		$user_msg = isset( $_POST['user_message'] ) ? sanitize_text_field( wp_unslash( $_POST['user_message'] ) ) : '';

		if( empty( $user_msg ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'Message cannot be empty.', 'premium-addons-for-elementor' ) ) );
		}

		$user = wp_get_current_user();

		$email = $user->user_email;

		if( empty( $email ) ) {
			wp_send_json_error( array( 'message' => esc_html__( 'User email not found.', 'premium-addons-for-elementor' ) ) );
		}

		$element = isset( $_POST['element_name'] ) ? sanitize_text_field( wp_unslash( $_POST['element_name'] ) ) : '';

		$body = array(
			'email' => $email,
			'element' => $element,
			'message' => $user_msg,
		);

		$api_url = 'https://feedbackpa.leap13.com/wp-json/element-feedback/v2/add';

		$response = wp_safe_remote_request(
			$api_url,
			array(
				'headers'     => array(
					'Content-Type' => 'application/json',
				),
				'body'        => wp_json_encode( $body ),
				'timeout'     => 20,
				'method'      => 'POST',
				'httpversion' => '1.1',
			)
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( 'REQUEST ERR' );

		}

		if ( ! isset( $response['response'] ) || ! is_array( $response['response'] ) ) {
			wp_send_json_error( 'REQUEST UNKNOWN' );

		}

		if ( ! isset( $response['body'] ) ) {
			wp_send_json_error( 'REQUEST PAYLOAD EMPTY' );

		}

		wp_send_json_success( ( $response['body'] ) );

	}

	/**
	 * Creates and returns an instance of the class
	 *
	 * @since 1.0.0
	 * @access public
	 *
	 * @return object
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {

			self::$instance = new self();

		}

		return self::$instance;
	}


}
