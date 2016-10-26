<?php
/**
 * IDX Broker API
 *
 * @package WP-IDX-Broker-API
 */

/*
* Plugin Name: WP IDX Broker API
* Plugin URI: https://github.com/wp-api-libraries/wp-idxbroker-api
* Description: Perform API requests to IDX Broker in WordPress.
* Author: WP API Libraries
* Version: 1.0.0
* Author URI: https://wp-api-libraries.com
* GitHub Plugin URI: https://github.com/wp-api-libraries/wp-idxbroker-api
* GitHub Branch: master
*/

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Check if class exists. */
if ( ! class_exists( 'IdxBrokerAPI' ) ) {
	/**
	 * IdxBrokerAPI class.
	 */
	class IdxBrokerAPI {

		/**
		 * API URL.
		 *
		 * @var [String]
		 */
		private $api_url = 'https://api.idxbroker.com/';

		/**
		 * HTTP request arguments.
		 *
		 * (default value: array())
		 *
		 * @var array
		 * @access protected
		 */
		private $args = array();

		/**
		 * IDX Broker route to make a the call to.
		 *
		 * @var [String]
		 */
		private $route;

		/**
		 * Raw response from IDX Broker server.
		 *
		 * @var [String]
		 */
		private $response;

		/**
		 * Response code from the server
		 *
		 * @var [Int]
		 */
		public $code;

		/**
		 * Text domain to be used for i18n
		 * @var [String]
		 */
		private $textdomain;

		/**
		 * __construct function.
		 *
		 * @access public
		 * @param  [String] $api_key : IDX Broker API key.
		 * @return void
		 */
		public function __construct( $api_key, $partner_key = null, $outputtype = 'json', $apiversion = '1.4.0', $textdomain = 'wp-idxbroker-api' ) {

			$this->args['headers'] = array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'accesskey' => $api_key,
				'ancillarykey' => $partner_key,
				'outputtype' => $outputtype,
				'apiversion' => $apiversion,
			);

			$this->textdomain = $textdomain;
		}

		/**
		 * Request function.
		 *
		 * @access public
		 * @return Results
		 */
		public function request() {
			$result = false;
			$this->response = wp_remote_request( $this->api_url . $this->route,  $this->args );

			$this->get_response_code();

			if ( in_array( $this->code, array( 200, 204 ) ) ) {
				$result = json_decode( wp_remote_retrieve_body( $this->response ), true );
			}

			return $result;
		}

		/**
		 * Builds the request for the API call to IDX Broker.
		 *
		 * @param  [String] $route  : The route to make the call to.
		 * @param  [array]  $fields : Array containing the http method and body
		 *                          	of call. Optional for GET requests.
		 * @return [Object]         : IdxBrokerAPI Object.
		 */
		public function build_request( $route, $fields = array() ) {
			$this->route = ( isset( $route ) ) ? $route : '';
			$this->args['method'] = ( isset( $fields['method'] ) ) ? $fields['method'] : 'GET';
			$this->args['body'] = ( isset( $fields['body'] ) ) ? $fields['body'] : '';
			return $this;
		}

		/**
		 * Saves the hourly API key usage count.
		 */
		private function check_usage() {
			return $hour_usage = wp_remote_retrieve_header( $this->response, 'hourly-access-key-usage' );
		}

		/**
		 * Gets the response code from the response.
		 */
		private function get_response_code() {
			$this->code = wp_remote_retrieve_response_code( $this->response );

			if ( WP_DEBUG && ! in_array( $this->code, array( 200, 204 ) ) ) {
				error_log( "[$this->route] response: $this->code" );
			}
		}

		/**
		 * Response code message.
		 *
		 * @param  [String] $code : Response code to get message from.
		 * @return [String]       : Message corresponding to response code sent in.
		 */
		public function response_code_msg( $code = '' ) {
			switch ( $code ) {
				case 200:
					$msg = __( 'OK.', $this->textdomain );
					break;
				case 204:
					$msg = __( 'OK, nothing returned.', $this->textdomain );
					break;
				case 400:
					$msg = __( 'Required parameter missing or invalid.', $this->textdomain );
					break;
				case 401:
					$msg = __( 'Accesskey not valid or revoked.', $this->textdomain );
					break;
				case 403.4:
					$msg = __( 'URL provided is not using SSL (HTTPS).', $this->textdomain );
					break;
				case 404:
					$msg = __( 'Invalid API component specified.', $this->textdomain );
					break;
				case 405:
					$msg = __( 'Method requested is invalid. This usually indicates a typo or that you may be requested a method that is part of a different API component.', $this->textdomain );
					break;
				case 406:
					$msg = __( 'Accesskey not provided.', $this->textdomain );
					break;
				case 409:
					$msg = __( 'Duplicate unique data detected.', $this->textdomain );
					break;
				case 412:
					$msg = __( "Account is over it's hourly access limit.", $this->textdomain );
					break;
				case 413:
					$msg = __( 'Requested entity too large.', $this->textdomain );
					break;
				case 416:
					$msg = __( 'Requested time range not satisfiable.', $this->textdomain );
					break;
				case 417:
					$msg = __( 'There are more saved links in the account than allowed through the API.', $this->textdomain );
					break;
				case 500:
					$msg = __( 'General system error. Please try again later or contact IDX support.', $this->textdomain );
					break;
				case 503:
					$msg = __( 'Scheduled or emergency API maintenance will result in 503 errors.', $this->textdomain );
					break;
				case 521:
					$msg = __( 'Temporary error. There is a possibility that not all API methods are affected.', $this->textdomain );
					break;
				default:
					$msg = __( 'Response code unknown', $this->textdomain );
					break;
			}
			return $msg;
		}
	} //End Class.

} // End If.
