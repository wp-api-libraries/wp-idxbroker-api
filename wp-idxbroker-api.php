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
		public function get_idx_domain() {
			// Make API call to systemlinks cuz IDX Broker doesnt send it in accounts info. ¯\_(ツ)_/¯
			$links = $this->build_request( 'clients/systemlinks?rf=url' )->request();

			// Default to false.
			$domain = false;

			// Parse URL if successful.
			if ( isset( $links[0]['url'] ) ) {
				$data = parse_url( $links[0]['url'] );
				$domain['scheme'] = $data['scheme'];
				$domain['url'] = $data['host'];
			}

			return $domain;
		}

		/* --------------------------------------------- Partners Endpoints -------------------------------------------- */

		/**
		 * Get a list of all agents for your clients.
		 *
		 * @api GET
		 *
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_partners_aggregatedagents( $args = array() ) {
			// Prepare request.
			$route = 'partners/aggregatedagents';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Get a list of featured MLS properties.
		 *
		 * @api GET
		 *
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_partners_aggregatedfeatured( $args = array() ) {
			// Prepare request.
			$route = 'partners/aggregatedfeatured';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Get a list of all leads.
		 *
		 * For bandwidth and memory considerations there is a limit of 5,000 on the number of leads that can be returned in
		 * any single request. Even if a full week of data is requested this limit will only be encountered if your clients
		 * have a combined average 30+ leads created, updated, or active per hour (as such it will be most common when
		 * requesting leads based on last property update date). If this limit is exceeded a 413 -Requested Entity Too
		 * Large error is returned. If encountered a smaller interval will need to be used.
		 *
		 * @api GET
		 *
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_partners_aggregatedleads( $args = array() ) {
			// Prepare request.
			$route = 'partners/aggregatedleads';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Get a list of all leads traffic history.
		 *
		 * Note: For bandwidth and memory considerations there is a limit of 5,000 on the number of searches that can be
		 * returned in any single request.
		 *
		 * @api GET
		 *
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_partners_aggregatedleadtraffic( $args = array() ) {
			// Prepare request.
			$route = 'partners/aggregatedleadtraffic';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * This method gives the status for all MLS listings (not supplemental) broken down by client account ID. This
		 * includes sold/pending listings with an unknown status which are not usually returned by sold/pending api methods.
		 * This is helpful if you need to know when previously gathered featured properties have left the market.
		 *
		 * @api GET
		 *
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_partners_aggregatedlistingstatus( $args = array() ) {
			// Prepare request.
			$route = 'partners/aggregatedlistingstatus';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Get a list of all lead saved properties.
		 *
		 * For bandwidth and memory considerations there is a limit of 5,000 on the number of searches that can be returned
		 * in any single request.
		 *
		 * @api GET
		 *
		 * @access public
 		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_partners_aggregatedproperties( $args = array() ) {
			// Prepare request.
			$route = 'partners/aggregatedproperties';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Get a list of all lead saved searches.
		 *
		 * For bandwidth and memory considerations there is a limit of 5,000 on the number of searches that can be returned
		 * in any single request.
		 *
		 * @api GET
		 *
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_partners_aggregatedsearches( $args = array() ) {
			// Prepare request.
			$route = 'partners/aggregatedsearches';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Get a list of sold/pending MLS properties.
		 *
		 * Output fields may or may not be populated depending on how the information was entered into the IDX system.
		 *
		 * We are planning to add the ability to query by the date the property left the market and, for sold listings, the
		 * date it was sold in a future update.
		 *
		 * @api GET
		 *
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_partners_aggregatedsoldpending( $args = array() ) {
			// Prepare request.
			$route = 'partners/aggregatedsoldpending';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Get a list of supplemental (non-MLS) properties.
		 *
		 * Output fields may or may not be populated depending on how the information was entered into the IDX system.
		 *
		 * @api GET
		 *
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_partners_aggregatedsupplemental( $args = array() ) {
			// Prepare request.
			$route = 'partners/aggregatedsupplemental';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Get the default api version.
		 *
		 * @api GET
		 *
		 * @access public
		 * @return string API version.
		 */
		public function get_partners_apiversion() {
			return $this->build_request( 'partners/apiversion' )->request();
		}

		/**
		 * List of available MLSs with their fees.
		 *
		 * @api GET
		 *
		 * @access public
		 * @return array Array of API results.
		 */
		public function get_partners_availablemls() {
			return $this->build_request( 'partners/availablemls' )->request();
		}

		/**
		 * A list of clients available to a given partner. The list of clients can be filtered by GET values.
		 *
		 * @api GET
		 *
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_partners_clients( $args = array() ) {
			// Prepare request.
			$route = 'partners/clients';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * This is a simple, access anywhere, method for getting a list of all API components available.
		 *
		 * @api GET
		 *
		 * @access public
		 * @return array Array of API results.
		 */
		public function get_partners_listcomponents() {
			return $this->build_request( 'partners/listcomponents' )->request();
		}

		/**
		 * A simple method for listing all available methods in the current API component. This method will also list which
		 * request methods (GET, PUT, POST, or DELETE) are supported by each method in addition to each method status.
		 *
		 * @api GET
		 *
		 * @access public
		 * @return array Array of API results.
		 */
		public function get_partners_listmethods() {
			return $this->build_request( 'partners/listmethods' )->request();
		}

		/**
		 * Gives the names and IDs of all available property types. This method differs from the property type lookup method
		 * in the client API component in that it can look up property types for any active Platinum MLS, not just those for
		 * which the client is a member.
     *
     * Note: The IDX property types are those used for multiple MLS searches and are equivalent to the property types
     * used in the original IDX product. The data returned is structured as:
     *
     * idxPropTypes
     *     * parentPtID - the numeric ID for IDX property types; seen as parentPtID when retrieving property information.
     *     * pt - the 2 to 3 letter abbreviated property type as seen in multiple MLS search queries as the variable pt.
     *     * propertyType - the human friendly property type name.
     * [idxID] in the format a### (this element will not be present at all if no IDX ID is provided)
     *     * mlsPtID - the numeric ID given to MLS property types; seen as parentPtID when retrieving property
     *                 information and in single MLS search queries as the variable pt.
     *     * propertyType - the human friendly property type name.
     *     * parentPtID - the ID of the IDX property type to which this MLS property type belongs.
		 *
		 * @api GET
		 *
		 * @param  string         $idx_id  The IDX ID of the MLS from which you need property type information. If no IDX ID
		 *                                 is specified then only the IDX property types (parentPtID) will be returned.
		 * @param  string | array $rf      A string or an array of strings of return field names.
		 * @return array                   Array of API results.
		 */
		public function get_partners_propertytypes( $idx_id = '', $rf = '' ) {
			// Prepare request.
			$route = ('' === $idx_id ) ? "partners/propertytypes" : "partners/propertytypes/$idx_id";
			$route = add_query_arg( array( 'rf' => $rf ), $route );

			return $this->build_request( $route )->request();
		}


		/* --------------------------------------------- Client Endpoints ----------------------------------------------- */


		/**
		 * Get your account type.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getAccounttypes API Doc.
		 *
		 * @return string "IDX Broker Platinum" | "IDX Broker Lite"
		 */
		public function get_clients_accounttype() {
			 return $this->build_request( 'clients/accounttype' )->request();
		}

		/**
		 * View agent information on a multi-user account.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getAgents API Doc.
		 *
		 * @param  array $args  Query args to send in to API call.
		 * @return array Array of API results.
		 */
		public function get_clients_agents( $args = array() ) {
			// Prepare request.
			$route = 'clients/agents';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Get the default api version.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-apiversion API Doc.
		 *
		 * @return array API version info.
		 */
		public function get_clients_apiversion() {
			return $this->build_request( 'clients/apiversion' )->request();
		}

		/**
		 * Returns the cities available in each of a client's city lists. Since a client can build any number of city
		 * lists this method requires the ID of which list you want to view. To get a list of all city lists available do
		 * not send the primary request ID. The default list on each account has the id combinedActiveMLS
		 *
		 * @api GET
		 *
		 * @param  string         $list_id City list id
		 * @param  string | array $rf      A string or an array of strings of return field names.
		 * @return array                   Array of API results.
		 */
		public function get_clients_cities( $list_id = '', $rf = '' ) {
			// Prepare request.
			$route = ('' === $list_id ) ? "clients/cities" : "clients/cities/$list_id";
			$route = add_query_arg( array( 'rf' => $rf ), $route );

			return $this->build_request( $route )->request();
		}
		public function get_clients_citieslistname() {
		}
		public function get_clients_counties() {
		}
		public function get_clients_countieslistname() {
		}
		public function post_clients_dynamicwrapperurl() {
		}
		public function get_clients_featured() {
		}
		public function get_clients_listallowedfields() {
		}
		public function get_clients_listcomponents() {
		}
		public function get_clients_listing() {
		}
		public function get_clients_listmethods() {
		}
		public function get_clients_offices() {
		}
		public function get_clients_postalcodes() {
		}
		public function get_clients_postalcodeslistname() {
		}
		public function get_clients_properties() {
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

		/* MLS Endpoints. */

		/**
		 * [get_mls_age description]
		 *
		 * @return [type] [description]
		 */
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

		/* Leads Endpoints. */

		/**
		 * [add_bulk_leads description]
		 */
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

		/* Specialty Partner Endpoints. */

		/**
		 * [get_specialty_partner_pricing description]
		 *
		 * @return [type] [description]
		 */
		public function get_specialty_partner_pricing() {
		}
		public function add_specialty_partner_subscriber() {
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

	} //End Class.

} // End If.
