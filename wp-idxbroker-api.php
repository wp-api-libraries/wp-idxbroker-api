<?php
/**
 * IDX Broker API
 *
 * @package WP-API-Libraries\WP-IDX-Broker-API
 * @author sfgarza
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
	 *
	 * @package WP-IDX-Broker-API
	 */
	class IdxBrokerAPI {

		/**
		 * API URL.
		 *
		 * @var string
		 */
		private $api_url = 'https://api.idxbroker.com/';

		/**
		 * HTTP request arguments.
		 *
		 * (default value: array())
		 *
		 * @var Array
		 * @access protected
		 */
		protected $args = array();

		/**
		 * IDX Broker route to make a the call to.
		 *
		 * @var string
		 */
		protected $route;

		/**
		 * Raw response from IDX Broker server.
		 *
		 * @var string
		 */
		protected $response;

		/**
		 * Response code from the server
		 *
		 * @var int
		 */
		public $code;

		/**
		 * Text domain to be used for i18n
		 *
		 * @var string
		 */
		protected $textdomain;

		/**
		 * __construct function.
		 *
		 * @access public
		 * @param string $api_key      IDX Broker API key.
		 * @param string $partner_key  Ancillarykey.
		 * @param string $outputtype   XML or JSON.
		 * @param string $apiversion   Version of API to use.
		 * @param string $textdomain   Textdomain.
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
		 * @return array Array of API results.
		 */
		public function request() {
			$result = false;
			$this->response = wp_remote_request( $this->api_url . $this->route,  $this->args );

			$this->get_response_code();
			$this->check_usage();

			if ( in_array( $this->code, array( 200, 204 ) ) ) {
				$result = json_decode( wp_remote_retrieve_body( $this->response ), true );
			}

			return $result;
		}

		/**
		 * Builds the request for the API call to IDX Broker.
		 *
		 * @param  string $route  The route to make the call to.
		 * @param  array  $fields Array containing the http method and body
		 *                        of call. Optional for GET requests.
		 * @return IdxBrokerAPI   IdxBrokerAPI Object.
		 */
		public function build_request( $route, $fields = array() ) {
			$this->route = ( isset( $route ) ) ? $route : '';
			$this->args['method'] = ( isset( $fields['method'] ) ) ? $fields['method'] : 'GET';
			$this->args['body'] = ( isset( $fields['body'] ) ) ? $fields['body'] : '';
			return $this;
		}

		/**
		 * Saves the hourly API key usage count.
		 *
		 * @return string API hourly usage count.
		 */
		protected function check_usage() {
			return $hour_usage = wp_remote_retrieve_header( $this->response, 'hourly-access-key-usage' );
		}

		/**
		 * Gets the response code from the response.
		 */
		protected function get_response_code() {
			$this->code = wp_remote_retrieve_response_code( $this->response );

			if ( WP_DEBUG && ! in_array( $this->code, array( 200, 204 ) ) ) {
				error_log( "[$this->route] response: $this->code" );
			}
		}

		/**
		 * Get domain used for displaying IDX pages.
		 *
		 * @return array  Array containing domain 'scheme' & 'url'.
		 */
		public function get_idx_domain(){
			// Make API call to systemlinks cuz IDX Broker doesnt send it in accounts info. ¯\_(ツ)_/¯
			$links = $this->build_request( 'clients/systemlinks?rf=url' )->request();

			// Default to false.
			$domain = false;

			// Parse URL if successful.
			if( isset( $links[0]['url'] ) ){
				$data = parse_url( $links[0]['url'] );
				$domain['scheme'] = $data['scheme'];
				$domain['url'] = $data['host'];
			}

			return $domain;
		}

		/**
		 * Response code message.
		 *
		 * @param  string $code Response code to get message from.
		 * @return string       Message corresponding to response code sent in.
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
