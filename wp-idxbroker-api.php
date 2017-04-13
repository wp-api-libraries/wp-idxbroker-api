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
		public function __construct( $api_key, $partner_key = null, $outputtype = 'json', $apiversion = '1.4.0' ) {

			$this->args['headers'] = array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'accesskey' => $api_key,
				'ancillarykey' => $partner_key,
				'outputtype' => $outputtype,
				'apiversion' => $apiversion,
			);

			'wp-idxbroker-api' = $textdomain;
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
					$msg = __( 'OK.', 'wp-idxbroker-api' );
					break;
				case 204:
					$msg = __( 'OK, nothing returned.', 'wp-idxbroker-api' );
					break;
				case 400:
					$msg = __( 'Required parameter missing or invalid.', 'wp-idxbroker-api' );
					break;
				case 401:
					$msg = __( 'Accesskey not valid or revoked.', 'wp-idxbroker-api' );
					break;
				case 403.4:
					$msg = __( 'URL provided is not using SSL (HTTPS).', 'wp-idxbroker-api' );
					break;
				case 404:
					$msg = __( 'Invalid API component specified.', 'wp-idxbroker-api' );
					break;
				case 405:
					$msg = __( 'Method requested is invalid. This usually indicates a typo or that you may be requested a method that is part of a different API component.', 'wp-idxbroker-api' );
					break;
				case 406:
					$msg = __( 'Accesskey not provided.', 'wp-idxbroker-api' );
					break;
				case 409:
					$msg = __( 'Duplicate unique data detected.', 'wp-idxbroker-api' );
					break;
				case 412:
					$msg = __( "Account is over it's hourly access limit.", 'wp-idxbroker-api' );
					break;
				case 413:
					$msg = __( 'Requested entity too large.', 'wp-idxbroker-api' );
					break;
				case 416:
					$msg = __( 'Requested time range not satisfiable.', 'wp-idxbroker-api' );
					break;
				case 417:
					$msg = __( 'There are more saved links in the account than allowed through the API.', 'wp-idxbroker-api' );
					break;
				case 500:
					$msg = __( 'General system error. Please try again later or contact IDX support.', 'wp-idxbroker-api' );
					break;
				case 503:
					$msg = __( 'Scheduled or emergency API maintenance will result in 503 errors.', 'wp-idxbroker-api' );
					break;
				case 521:
					$msg = __( 'Temporary error. There is a possibility that not all API methods are affected.', 'wp-idxbroker-api' );
					break;
				default:
					$msg = __( 'Response code unknown', 'wp-idxbroker-api' );
					break;
			}
			return $msg;
		}

	/* Partners. */

	public function get_aggregated_agents() {

	}

	public function get_aggregated_featured() {

	}

	public function get_aggregated_leads() {

	}

	public function get_aggregated_lead_traffic() {

	}

	public function get_aggregated_listing_status() {

	}

	public function get_aggregated_properties() {

	}

	public function get_aggregated_searches() {

	}

	public function get_aggregated_soldpending() {

	}

	public function get_aggregated_supplemental() {

	}

	public function get_partners_api_version() {

	}

	public function get_available_mls() {

	}

	public function get_clients() {

	}

	public function list_components() {

	}

	public function list_methods() {

	}

	public function get_partners_propertytypes() {

	}

	/* Clients. */

	public function get_account_type() {

	}

	public function get_agents() {

	}

	public function get_api_version() {

	}

	public function get_cities() {

	}

	public function get_cities_listname() {

	}

	public function get_counties() {

	}

	public function get_counties_listname() {

	}

	public function send_dynamic_wrapper_url() {

	}

	public function get_featured() {

	}

	public function get_list_allowed_fields() {

	}

	public function get_list_components() {

	}

	public function get_listing() {

	}

	public function get_list_methods() {

	}

	public function get_offices() {

	}

	public function get_postalcodes() {

	}

	public function get_postalcodes_listname() {

	}

	public function get_properties() {

	}

	public function add_savedlink() {

	}

	public function delete_savedlink() {

	}

	public function get_savedlinks() {

	}

	public function update_savedlink() {

	}

	public function get_searchquery() {

	}

	public function get_soldpending() {

	}

	public function delete_supplemental() {

	}

	public function get_supplemental() {

	}

	public function add_supplemental() {

	}

	public function update_supplemental() {

	}

	public function get_systemlinks() {

	}

	public function get_widgets() {

	}

	public function delete_wrapper_cache() {

	}

	public function get_zipcodes() {

	}

	/* MLS. */

	public function get_mls_age() {

	}

	public function get_approved_mls() {

	}

	public function get_mls_cities() {

	}

	public function get_mls_counties() {

	}

	public function get_mls_list_components() {

	}

	public function get_mls_list_methods() {

	}

	public function get_mls_postalcodes() {

	}

	public function get_mls_prices() {

	}

	public function get_mls_property_count() {

	}

	public function get_mls_property_types() {

	}

	public function get_mls_search_fields() {

	}

	public function get_mls_search_field_values() {

	}

	public function get_mls_zipcodes() {

	}

	/* LEADS. */

	public function add_bulk_leads() {

	}

	public function update_bulk_leads() {

	}

	public function delete_lead() {

	}

	public function get_lead() {

	}

	public function update_lead() {

	}

	public function add_lead() {

	}

	public function get_lead_traffic() {

	}

	public function get_lead_list_components() {

	}

	public function get_lead_list_methods() {

	}

	public function delete_lead_note() {

	}

	public function add_lead_note() {

	}

	public function update_lead_note() {

	}

	public function get_lead_note() {

	}

	public function delete_lead_property() {

	}

	public function add_lead_property() {

	}

	public function update_lead_property() {

	}

	public function get_lead_property() {

	}

	public function delete_lead_search() {

	}

	public function add_lead_search() {

	}

	public function update_lead_search() {

	}

	public function get_lead_search() {

	}


	/* Specialty Partner. */

	public function get_specialty_partner_pricing() {

	}

	public function add_specialty_partner_subscriber() {

	}

	} //End Class.

} // End If.
