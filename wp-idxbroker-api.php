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
* Text Domain: wp-idxbroker-api
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
		public function __construct( $api_key, $partner_key = null, $outputtype = 'json', $apiversion = '1.4.0' ) {

			$this->args['headers'] = array(
				'Content-Type' => 'application/x-www-form-urlencoded',
				'accesskey' => $api_key,
				'ancillarykey' => $partner_key,
				'outputtype' => $outputtype,
				'apiversion' => $apiversion,
			);

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
	/**
	 * get_aggregated_agents function.
	 *
	 * @access public
	 * @param string $rf (default: '') Array of Return Fields
	 * @param string $client_chunk (default: '')
	 * @param string $include_disabled_accounts (default: '')
	 * @param string $offset (default: '')
	 * @param string $limit (default: '')
	 * @return void
	 */
	public function get_aggregated_agents( $rf = '', $client_chunk = '', $include_disabled_accounts = '', $offset = '', $limit = '' ) {

		$results = $this->build_request( 'partners/aggregatedagents??rf[]=' . $rf . '&clientChunk=' . $client_chunk . '&includeDisabledAccounts=' . $include_disabled_accounts . '&offset=' . $offset . '&limit=' . $limit );
		return $results;

	}


	/**
	 * get_aggregated_featured function.
	 *
	 * @access public
	 * @param string $date_type (default: '')
	 * @param string $interval (default: '')
	 * @param string $start_date_time (default: '')
	 * @param string $rf (default: '') Array of Return Fields
	 * @param string $client_chunk (default: '')
	 * @param string $include_disabled_accounts (default: '')
	 * @param string $offset (default: '')
	 * @param string $limit (default: '')
	 * @param string $disclaimers (default: '')
	 * @return void
	 */
	public function get_aggregated_featured( $date_type = '', $interval = '', $start_date_time = '', $rf = '', $client_chunk = '', $include_disabled_accounts = '', $offset = '', $limit = '', $disclaimers = '' ) {


	}

	/**
	 * get_aggregated_leads function.
	 *
	 * @access public
	 * @param string $date_type (default: '')
	 * @param string $interval (default: '')
	 * @param string $start_date_time (default: '')
	 * @param string $rf (default: '')
	 * @param string $client_chunk (default: '')
	 * @param string $include_disabled_accounts (default: '')
	 * @return void
	 */
	public function get_aggregated_leads( $date_type = '', $interval ='', $start_date_time = '', $rf = '', $client_chunk = '', $include_disabled_accounts = '' ) {
	}

	/**
	 * get_aggregated_lead_traffic function.
	 *
	 * @access public
	 * @param string $interval (default: '')
	 * @param string $start_date_time (default: '')
	 * @param string $rf (default: '')
	 * @param string $client_chunk (default: '')
	 * @param string $include_disabled_accounts (default: '')
	 * @return void
	 */
	public function get_aggregated_lead_traffic( $interval = '', $start_date_time = '', $rf = '', $client_chunk = '', $include_disabled_accounts = '' ) {
	}

	/**
	 * get_aggregated_listing_status function.
	 *
	 * @access public
	 * @param string $filter_field (default: '')
	 * @param string $filter_value (default: '')
	 * @param string $client_chunk (default: '')
	 * @param string $include_disabled_accounts (default: '')
	 * @return void
	 */
	public function get_aggregated_listing_status( $filter_field = '', $filter_value = '', $client_chunk = '', $include_disabled_accounts = '' ) {
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
