<?php
/**
 * The Global functionality of the plugin.
 *
 * Defines the functionality loaded both on frontend.
 *
 * @since      1.0.15
 * @package    RankMath
 * @subpackage RankMath\Rest
 * @author     Rank Math <support@rankmath.com>
 */

namespace RankMath\Rest;

use WP_Error;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Controller;
use RankMath\Admin\Admin_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Front class.
 */
class Front extends WP_REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = \RankMath\Rest\Rest_Helper::BASE;
	}

	/**
	 * Registers the routes for the objects of the controller.
	 */
	public function register_routes() {

		register_rest_route(
			$this->namespace,
			'/disconnectSite',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'disconnect_site' ],
				'permission_callback' => [ $this, 'check_api_key' ],
				'args'                => $this->get_disconnect_site_args(),
			]
		);
	}

	/**
	 * Check API key in request.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return bool                     Whether the API key matches or not.
	 */
	public function check_api_key( WP_REST_Request $request ) {
		$token = $request->get_param( 'token' );
		$data  = Admin_Helper::get_registration_data();

		return isset( $data['api_key'] ) && $token === $data['api_key'];
	}

	/**
	 * Disconnect website.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function disconnect_site( WP_REST_Request $request ) {
		Admin_Helper::get_registration_data( false );

		return [
			'code'    => 'site_disconnected',
			'message' => esc_html__( 'Site disconnected successfully.', 'rank-math' ),
		];
	}


	/**
	 * Get disconnect site endpoint arguments.
	 *
	 * @return array
	 */
	private function get_disconnect_site_args() {
		return [
			'token' => [
				'type'              => 'string',
				'required'          => true,
				'description'       => esc_html__( 'Site token', 'rank-math' ),
				'validate_callback' => [ '\\RankMath\\Rest\\Rest_Helper', 'is_param_empty' ],
			],
		];
	}
}
