<?php
/**
 * Library for accessing the IDX Broker API on WordPress
 *
 * @link http://middleware.idxbroker.com/docs/api/methods/index.html API Documentation
 * @package WP-API-Libraries\WP-IDX-Broker-API
 */

/*
* Plugin Name: WP IDX Broker API
* Plugin URI: https://github.com/wp-api-libraries/wp-idxbroker-api
* Description: Perform API requests to IDX Broker in WordPress.
* Author: WP API Libraries
* Version: 1.1.0
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
	 * A WordPress API library for accessing the IDX Broker API.
	 *
	 * @version 1.1.0
	 * @link http://middleware.idxbroker.com/docs/api/methods/index.html API Documentation
   * @package WP-API-Libraries\WP-IDX-Broker-API
	 * @author Santiago Garza <https://github.com/sfgarza>
   * @author imFORZA <https://github.com/imforza>
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

			if ( in_array( (int) $this->code, array( 200, 204 ), true ) ) {
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
		 * @return self           IdxBrokerAPI Object.
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
			return wp_remote_retrieve_header( $this->response, 'hourly-access-key-usage' );
		}

		/**
		 * Gets the response code from the response.
		 */
		protected function get_response_code() {
			$this->code = wp_remote_retrieve_response_code( $this->response );

			if ( WP_DEBUG && ! in_array( (int) $this->code, array( 200, 204 ), true ) ) {
				error_log( "[$this->route] response: $this->code" );
			}
		}

		/**
		 * Get domain used for displaying IDX pages.
		 *
		 * @return array  Array containing domain 'scheme' & 'url'.
		 */
		public function get_idx_domain() {
			// Make API call to systemlinks cuz IDX Broker doesnt send it in accounts info. ¯\_(ツ)_/¯.
			$links = $this->get_clients_systemlinks( 'url' );
			// Default to false.
			$domain = false;

			// Parse URL if successful.
			if ( isset( $links[0]['url'] ) ) {
				$data = wp_parse_url( $links[0]['url'] );
				$domain['scheme'] = $data['scheme'];
				$domain['url'] = $data['host'];
				$domain['full'] = $data['scheme'] . '://' . $data['host'];
			}

			return $domain;
		}

		/*
		 -------------------------------------------------------------------------------------------------------------
		 ------------------------------------------- Partners Endpoints ----------------------------------------------
		 -------------------------------------------------------------------------------------------------------------
		 */

		/**
		 * Get a list of all agents for your clients.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getAggregatedagents Documentation
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array        All available agents.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getAggregatedfeatured Documentation
		 * @access public
		 * @param  array $args Query args to send in to API call.
		 * @return array       List of featured MLS properties for each client.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getAggregatedleads Documentation
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array The applicable client account ID, lead ID, first name, last name, email address, address, city,
		 *               state/province, country, zipCode, phone number, ID of the agent assigned, email format (html or
		 *               plain text), disabled status (y/n), allowed to log in to their account (y/n), will receive property
		 *               updates (y/n), subscribe date, last edited, last login date, last property update date, last
		 *               activity type, and last activity date.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getAggregatedleadtraffic Documentation
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array        The applicable client account ID, date, lead ID, IP , page, and referrer.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getAggregatedlistingstatus Documentation
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array        MLS listings along with their statuses.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getAggregatedproperties Documentation
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Search ID, the applicable client account ID, lead ID, page ID, search name, search parameters, lead
		 *               will receive property updates (y/n), created date, last edited date.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getAggregatedsearches Documentation
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array Search ID, the applicable client account ID, lead ID, page ID, search name, search parameters, lead
		 *               will receive property updates (y/n), created date, last edited date.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getAggregatedsoldpending Documentation
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array        List of soldpending properties for each client.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getAggregatedsupplemental Documentation
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array        List of supplemental (non-MLS) properties for each client.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-apiversion Documentation
		 * @access public
		 * @return string The default api version.
		 */
		public function get_partners_apiversion() {
			return $this->build_request( 'partners/apiversion' )->request();
		}

		/**
		 * List of available MLSs with their fees.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getAvailableMls Documentation
		 * @access public
		 * @return array List of available MLSs with their fees.
		 */
		public function get_partners_availablemls() {
			return $this->build_request( 'partners/availablemls' )->request();
		}

		/**
		 * A list of clients available to a given partner. The list of clients can be filtered by GET values.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getClients Documentation
		 * @access public
		 * @param  array $args  Query args to send in to API call.
		 * @return array The account ID, company name, display name, account status, and current API key of each client or
		 *               clients matching the filter values.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getListcomponents Documentation
		 * @access public
		 * @return array All available APIs/Components.
		 */
		public function get_partners_listcomponents() {
			return $this->build_request( 'partners/listcomponents' )->request();
		}

		/**
		 * A simple method for listing all available methods in the current API component. This method will also list which
		 * request methods (GET, PUT, POST, or DELETE) are supported by each method in addition to each method status.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-listmethods Documentation
		 * @access public
		 * @return array Basic information about all available methods in this API.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Partners-getPropertytypess Documentation
		 * @param  string       $idx_id  The IDX ID of the MLS from which you need property type information. If no IDX ID
		 *                                 is specified then only the IDX property types (parentPtID) will be returned.
		 * @param  string|array $rf      A string or an array of strings of return field names.
		 * @return array                 An array containing the IDX property types and, if an IDX ID has been provided,
		 *                               the MLS's property types and their IDs.
		 */
		public function get_partners_propertytypes( $idx_id = '', $rf = '' ) {
			// Prepare request.
			$route = ('' === $idx_id ) ? 'partners/propertytypes' : "partners/propertytypes/$idx_id";
			$args = array(
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}


		/*
		 -------------------------------------------------------------------------------------------------------------
		 ------------------------------------------- Client Endpoints ------------------------------------------------
		 -------------------------------------------------------------------------------------------------------------
		 */

		/**
		 * Get your account type.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getAccounttypes Documentation.
		 *
		 * @return string Account type.
		 */
		public function get_clients_accounttype() {
			 return $this->build_request( 'clients/accounttype' )->request();
		}

		/**
		 * View agent information on a multi-user account.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getAgents Documentation.
		 *
		 * @param  array $args  Query args to send in to API call.
		 * @return array        All agents on the account or those matching filter values.
		 */
		public function get_clients_agents( $args = array() ) {
			$route = 'clients/agents';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Get the default api version.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-apiversion Documentation.
		 *
		 * @return array The default api version.
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
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getCities Documentation.
		 * @param  string       $list_id City list id.
		 * @param  string|array $rf      A string or an array of strings of return field names.
		 * @return array                 All cities in a given list or, if no list ID is provided, a list of list IDs.
		 */
		public function get_clients_cities( $list_id = '', $rf = '' ) {
			$route = ('' === $list_id ) ? 'clients/cities' : "clients/cities/$list_id";
			$args = array(
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Returns the IDs and names for each of a client's city lists including MLS city lists. To get the list of all city
		 * lists available do not send the primary request ID. The default list on each account has the ID combinedActiveMLS
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getCitieslistname Documentation.
		 *
		 * @return array A list of city list IDs and names.
		 */
		public function get_clients_citieslistname() {
			return $this->build_request( 'clients/citieslistname' )->request();
		}

		/**
		 * Returns the counties available in each of a client's county lists. Since a client can build any number of county
		 * lists this method requires the ID of which list you want to view. To get a list of all county lists available do
		 * not send the primary request ID. The default list on each account has the id combinedActiveMLS.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getCounties Documentation.
		 * @param  string       $list_id If no ID is given a list of IDs is returned.
		 * @param  string|array $rf      A string or an array of strings of fields to return in the output..
		 * @return array                 All counties in a given list or, if no list ID is provided, a list of list IDs.
		 */
		public function get_clients_counties( $list_id = '', $rf = '' ) {
			$route = ('' === $list_id ) ? 'clients/counties' : "clients/counties/$list_id";
			$args = array(
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Returns the IDs and names for each of a client's counties lists including MLS counties lists. To get the list of
		 * all counties lists available do not send the primary request ID. The default list on each account has the ID
		 * combinedActiveMLS
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getCountieslistname Documentation.
		 *
		 * @return array A list of counties list IDs and names
		 */
		public function get_clients_countieslistname() {
			return $this->build_request( 'clients/countieslistname' )->request();
		}

		/**
		 * Update dynamic wrapper url for global, pages and saved links. If savedLinkID, or pageID are not passed, the
		 * global dynamic wrapper url will be updated.
		 *
		 * @api POST
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-postDynamicwrapperurl Documentation.
		 * @param  string $dynamic_url  Dynamic wrapper url.
		 * @param  int    $savedlink_id Saved link ID if setting dynamic wrapper url for a specific saved link.
		 * @param  int    $page_id      Page ID if setting dynamic wrapper url for a specific page.
		 * @return mixed                No data returned on success.
		 */
		public function post_clients_dynamicwrapperurl( $dynamic_url, $savedlink_id = '', $page_id = '' ) {
			$fields['method'] = 'POST';
			$fields['body']['dynamicURL'] = $dynamic_url;

			if ( ! empty( $savedlink_id ) ) {
				$fields['body']['savedLinkID'] = $savedlink_id;
			}
			if ( ! empty( $page_id ) ) {
				$fields['body']['pageID'] = $page_id;
			}

			return $this->build_request( 'clients/dynamicwrapperurl', $fields )->request();
		}

		/**
		 * Returns a basic set of information for all of the client's featured (active) properties
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getFeatured Documentation.
		 *
		 * @param  array $args  Query args to send in to API call.
		 * @return array        Featured properties on the account.
		 */
		public function get_clients_featured( $args = array() ) {
			$route = 'clients/featured';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Returns the allowed returnable fields for a given listingID.
		 *
		 * Note: Valid ancillarykey is required in the request header.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-listallowedfields Documentation.
		 * @param  string $idx_id     The idxID of MLS.
		 * @param  string $listing_id The listing ID.
		 * @return array             List of fields that are returnable for the listingID.
		 */
		public function get_clients_listallowedfields( $idx_id, $listing_id ) {
			$route = "clients/listallowedfields/$idx_id/$listing_id";

			return $this->build_request( $route )->request();
		}

		/**
		 * This is a simple, access anywhere, method for getting a list of all API components available.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getListcomponents Documentation.
		 *
		 * @return array All available APIs/Components.
		 */
		public function get_clients_listcomponents() {
			return $this->build_request( 'clients/listcomponents' )->request();
		}


		/**
		 * Returns the detailed information for a given listingID.
		 *
		 * Note: Valid ancillarykey is required in the request header.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-listing Documentation.
		 * @param  string $idx_id      The idxID of MLS.
		 * @param  string $listing_id  The listing ID.
		 * @param  string $rf          Array of fields to return in the output.
		 * @param  bool   $disclaimers Include MLS disclaimer/courtesy in the response.
		 * @return array               List of fields that are returnable for the listingID.
		 */
		public function get_clients_listing( $idx_id, $listing_id, $rf = '', $disclaimers = '' ) {
			$route = "clients/listing/$idx_id/$listing_id";
			$args = array(
				'rf' => $rf,
				'disclaimers' => $disclaimers,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * A simple method for listing all available methods in the current API component. This method will also list which
		 * request methods (GET, PUT, POST, or DELETE) are supported by each method in addition to each method status.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-listmethods Documentation.
		 *
		 * @return array Basic information about all available methods in this API.
		 */
		public function get_clients_listmethods() {
			return $this->build_request( 'clients/listmethods' )->request();
		}

		/**
		 * View all offices on a mutli-user account.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getOffices Documentation.
		 *
		 * @param  array $args  Query args to send in to API call.
		 * @return array        All offices on the account or those matching filter values.
		 */
		public function get_clients_offices( $args = array() ) {
			$route = 'clients/offices';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Returns the postalcodes available in each of a client's postalcode lists. Since a client can build any number of
		 * postalcode lists this method requires the ID of which list you want to view. To get a list of all postalcode
		 * lists available do not send the primary request ID. The default list on each account has the id combinedActiveMLS.
		 *
		 * Note: This method was previously called as "zipcodes" but was changed to keep API format more international.
		 * Calls to "zipcodes" will be forwarded to "postalcodes" and "zipcodes" is listed as deprecated in the method list.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getPostalcodes Documentation.
		 * @param  string $list_id If no ID is given a list of IDs is returned.
		 * @param  array  $args    Query args to send in to API call.
		 * @return array           All postalcodes in a given list or, if no list ID is provided, a list of list IDs.
		 */
		public function get_clients_postalcodes( $list_id = '', $args = array() ) {
			$route = ( '' === $list_id ) ? 'clients/postalcodes' : "clients/postalcodes/$list_id";
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Returns the IDs and names for each of a client's postalcode lists including MLS postalcode lists. To get the list
		 * of all postal code lists available do not send the primary request ID. The default list on each account has the ID
		 * combinedActiveMLS
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getPostalcodeslistname Documentation.
		 *
		 * @return array A list of city list IDs and names
		 */
		public function get_clients_postalcodeslistname() {
			return $this->build_request( 'clients/postalcodeslistname' )->request();
		}

		/**
		 * Returns the search results for a provided saved link ID.
		 *
		 * Note: Valid ancillarykey is required in the request header.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-properties Documentation.
		 * @param  string $saved_links_id The ID of a client's saved link.
		 * @param  array  $args           Query args to send in to API call.
		 * @return array                  All property results for a provided Saved Link ID.
		 */
		public function get_clients_properties( $saved_links_id, $args = array() ) {
			$route = "clients/properties/$saved_links_id";
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Remove a new client saved link.
		 *
		 * @api DELETE
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-deleteSavedlinks Documentation.
		 * @param  string $saved_links_id The ID of a client's saved link.
		 * @return mixed                  Nothing on success.
		 */
		public function delete_clients_savedlink( $saved_links_id ) {
			$fields['method'] = 'DELETE';
			$route = "clients/savedlinks/$saved_links_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Get saved links for a given client account.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getSavedlinks Documentation.
		 * @param  array $args  Query args to send in to API call.
		 * @return array        All saved links on the account.
		 */
		public function get_clients_savedlinks( $args = array() ) {
			$route = 'clients/savedlinks';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Update an existing client's saved link
		 *
		 * This method is to be used at your own risk. We will NOT be held accountable for programmatic errors in your code
		 * or the improper use of search values or options within said values resulting in broken saved links.
		 *
		 * Note: The updatable fields need to be in a URL encoded, ampersand delineated query string format.
		 *
		 * Data Example:
		 * $data = array(
		 *  'linkName' => 'Good_side_of_tracks',
		 *  'pageTitle' => 'Good_side_of_tracks',
		 *  'linkTitle' => 'Good_side_of_tracks',
		 *  'queryString' => array('idxID' => 'a001', 'hp' => '200000')
		 * );
		 *
		 * @api POST
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-postSavedlinks Documentation.
		 * @param  string $saved_links_id The ID of a client's saved link.
		 * @param  array  $data           Savedlink fields to update.
		 * @return mixed                  If no POST data is supplied, then a list of updatable fields with format
		 *                                information is returned, otherwise on success 204 is returned.
		 */
		public function post_clients_savedlink( $saved_links_id, $data = array() ) {
			$fields['method'] = 'POST';
			$fields['body'] = $data;
			$route = "clients/savedlinks/$saved_links_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Create a new client saved link.
		 *
		 * Note: The updatable fields need to be in a URL encoded, ampersand delineated query string format. This action is
		 *       not allowed if the client has more than 1000 saved links.
		 *
		 * Data Example:
		 * $data = array(
		 *  'linkName' => 'Good_side_of_tracks',
		 *  'pageTitle' => 'Good_side_of_tracks',
		 *  'linkTitle' => 'Good_side_of_tracks',
		 *  'queryString' => array('idxID' => 'a001', 'hp' => '200000')
		 * );
		 *
		 * @api PUT
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-putSavedlinks Documentation.
		 * @param  array $data    Savedlink fields to create.
		 * @return mixed          If a client saved link is successfully created, the new saved link's ID will be
		 *                        returned. If no PUT data is supplied, then a list of updatable fields with format
		 *                        information is returned.
		 */
		public function put_clients_savedlink( $data = array() ) {
			$fields['method'] = 'PUT';
			$fields['body'] = $data;
			$route = 'clients/savedlinks';

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Performs search and returns the results.
		 *
		 * Note: Valid ancillarykey is required in the request header.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getSearchquery Documentation.
		 * @param  array $args  Query args to send in to API call.
		 * @return array        All available APIs/Components.
		 */
		public function get_clients_searchquery( $args = array() ) {
			$route = 'clients/searchquery';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Returns a basic set of information for all of the client's sold and pending properties. That is, those that have
		 * been removed from their MLS data.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getSoldpending Documentation.
		 * @param  array $args  Query args to send in to API call.
		 * @return array        Sold/pending properties on the account.
		 */
		public function get_clients_soldpending( $args = array() ) {
			$route = 'clients/soldpending';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Remove a clients supplemental property.
		 *
		 * This method is to be used at your own risk. We will NOT be held accountable for programmatic errors in your code
		 * or the improper use of search values or options within said values resulting in deletion of supplemental properties.
		 *
		 * @api DELETE
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-deleteSupplemental Documentation.
		 * @param  string $listing_id The listingID of a supplmental property.
		 * @return mixed              Nothing on success.
		 */
		public function delete_clients_supplemental( $listing_id ) {
			$fields['method'] = 'DELETE';
			$route = "clients/supplemental/$listing_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Returns a basic set of information for all of the client's supplemental (non-MLS) properties.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getSupplemental Documentation.
		 * @param  array $args  Query args to send in to API call.
		 * @return array        Supplemental properties on the account.
		 */
		public function get_clients_supplemental( $args = array() ) {
			$route = 'clients/supplemental';
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Update an existing supplemental listing.
		 *
		 * Note: if updating images, existing images are deleted and the new images are inserted instead for the listing.
		 *
		 * Data Example:
		 * $data = array(
		 *  'likeIdxID' => 'a001',
		 *  'likeMlsPtID' => '1',
		 *  'images' => array('http://example.com/image1.jpg', 'http://example.com/image2.jpg')
		 * );
		 *
		 * @api POST
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-postSupplemental Documentation.
		 * @param  string $listing_id The supplemental listing ID.
		 * @param  array  $data       Supplemental fields to update.
		 * @return mixed              If no POST data is supplied, then a list of updatable fields with format information
		 *                            is returned, otherwise on success 204 is returned.
		 */
		public function post_clients_supplemental( $listing_id, $data = array() ) {
			$fields['method'] = 'POST';
			$fields['body'] = $data;
			$route = "clients/supplemental/$listing_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Create a new supplemental listing.
		 *
		 * Note: likeIdxID and likeMlsPtID fields are required.
		 *
		 * Data Example:
		 * $data = array(
		 *  'likeIdxID' => 'a001',
		 *  'likeMlsPtID' => '1',
		 *  'images' => array('http://example.com/image1.jpg', 'http://example.com/image2.jpg')
		 * );
		 *
		 * @api PUT
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-putSupplemental Documentation.
		 * @param  array $data  Supplemental fields to create.
		 * @return mixed        If a supplemental listing is successfully created, the new supplemental listing ID will be
		 *                      returned. If no PUT data is supplied, then a list of updatable fields with format
		 *                      information is returned.
		 */
		public function put_clients_supplemental( $data = array() ) {
			$fields['method'] = 'PUT';
			$fields['body'] = $data;
			$route = 'clients/supplemental';

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Gathers all the pages system pages (search, featured, contact, etc) that can be directly linked to without
		 * additional property information being included in the URL.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getSystemlinks Documentation.
		 * @param  string|array $rf  String or array of fields to return in the output.
		 * @return array             The name, unique ID, and URL for all system links on the account. Additionally there
		 *                           is a boolean named systemresults. If true this is a property results page that requires
		 *                           additional parameters. This means the url can be useful when dynamically building
		 *                           results page links but should not be linked to directly. When a client has more than
		 *                           one MLS on their account, listings for search pages that can vary by MLS ID will
		 *                           include a subpages array element.
		 */
		public function get_clients_systemlinks( $rf = '' ) {
			$route = 'clients/systemlinks';
			$args = array(
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Gather all the URLs for javascript widgets on the user's account. These widgets can then be placed on the user's
		 * main site via the included URLs.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getWidgetsrc Documentation.
		 * @param  string|array $rf  String or array of fields to return in the output.
		 * @return array             The name, unique ID and URL for all javascript widgets that have been created on the
		 *                           user's account.
		 */
		public function get_clients_widgets( $rf = '' ) {
			$route = 'clients/widgetsrc';
			$args = array(
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Remove a clients wrapper cache.
		 *
		 * This method is to be used at your own risk. We will NOT be held accountable for programmatic errors in your code
		 * or the improper use of search values or options within said values resulting in deletion of supplemental properties.
		 *
		 * @api DELETE
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getWrappercache Documentation.
		 * @return mixed   Nothing on success.
		 */
		public function delete_clients_wrappercache() {
			$fields['method'] = 'DELETE';
			return $this->build_request( 'clients/wrappercache', $fields )->request();
		}

		/**
		 * Returns the zipcodes available in each of a client's zipcode lists. Since a client can build any number of
		 * zipcode lists this method requires the ID of which list you want to view. To get a list of all zipcode lists
		 * available do not send the primary request ID. The default list on each account has the id combinedActiveMLS.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Clients-getZipcodes Documentation.
		 * @param  string       $list_id If no ID is given a list of IDs is returned.
		 * @param  string|array $rf      String or array of fields to return in the output.
		 * @return array                 All zipcodes in a given list or, if no list ID is provided, a list of list IDs..
		 */
		public function get_clients_zipcodes( $list_id = '', $rf = '' ) {
			$route = ( '' === $list_id ) ? 'clients/zipcodes' : "clients/zipcodes/$list_id";
			$args = array(
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/*
		 -------------------------------------------------------------------------------------------------------------
		 --------------------------------------------- MLS Endpoints -------------------------------------------------
		 -------------------------------------------------------------------------------------------------------------
		 */

		/**
		 * Gives the date and time a particular MLS was last downloaded, processed and the last time images gathering was completed.
		 *
		 * Note: dates/times given are UTC.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getAge Documentation.
		 * @param  string       $idx_id  Format: x000 .
		 * @param  string|array $rf      String or array of fields to return in the output.
		 * @return array                 An array of timestamps for last downloaded, last processes and last images gathered.
		 */
		public function get_mls_age( $idx_id, $rf = '' ) {
			$route = "mls/age/$idx_id";
			$args = array(
				'rf' => $rf,
			);
			 $route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * This method provides all of the IDX IDs and names for all of the paperwork approved MLSs on the client's account.
		 *
		 * Note: This method was previously camelcased as "approvedMLS" but was made lower case to fit the API naming
		 * convention. Calls to "approvedMLS" will be forwarded to "approvedmls" and "approvedMLS" is listed as deprecated
		 * in the method list.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getApprovedmls Documentation.
		 * @param  string|array $rf  String or array of fields to return in the output.
		 * @return array             A list of IDs and names for all MLSs approved for display on the client account.
		 */
		public function get_mls_approvedmls( $rf = '' ) {
			$route = 'mls/approvedmls';
			$args = array(
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * All cities represented in the current set of MLS data are available from this method. The output can be filtered
		 * using additional GET parameters.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getCities Documentation.
		 * @param  string       $idx_id        Format: x000 .
		 * @param  string       $filter_field  The field to use when filtering output.
		 *                                     Allowed values: "cityID, cityName, stateAbrv, mlsPtID".
		 * @param  string       $filter_value  The value by which to filter. Conditional on use of filterField.
		 * @param  string|array $rf            String or array of fields to return in the output.
		 * @return array                       Available cities along with applicable city ID, property type, and state as
		 *                                     well as a count of the number of occurrences for each value.
		 */
		public function get_mls_cities( $idx_id, $filter_field = '', $filter_value = '', $rf = '' ) {
			$route = "mls/cities/$idx_id";
			$args = array(
				'filterField' => $filter_field,
				'filterValue' => $filter_value,
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * All counties represented in the current set of MLS data are available from this method. The output can be
		 * filtered using additional GET parameters.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getCounties Documentation.
		 * @param  string       $idx_id        Format: x000.
		 * @param  string       $filter_field  The field to use when filtering output.
		 *                                     Allowed values: "countyID, countyName, stateAbrv, mlsPtID".
		 * @param  string       $filter_value  The value by which to filter. Conditional on use of filterField.
		 * @param  string|array $rf            String or array of fields to return in the output.
		 * @return array                       Available counties along with applicable county ID, property type, and state
		 *                                     as well as a count of the number of occurrences of each value.
		 */
		public function get_mls_counties( $idx_id, $filter_field = '', $filter_value = '', $rf = '' ) {
			$route = "mls/counties/$idx_id";
			$args = array(
				'filterField' => $filter_field,
				'filterValue' => $filter_value,
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * This is a simple, access anywhere, method for getting a list of all API components available.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getListcomponents Documentation.
		 *
		 * @return array All available APIs/Components
		 */
		public function get_mls_listcomponents() {
			return $this->build_request( 'mls/listcomponents' )->request();
		}

		/**
		 * A simple method for listing all available methods in the current API component. This method will also list which
		 * request methods (GET, PUT, POST, or DELETE) are supported by each method in addition to each method status.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-listmethods Documentation.
		 *
		 * @return array Basic information about all available methods in this API.
		 */
		public function get_mls_listmethods() {
			return $this->build_request( 'mls/listmethods' )->request();
		}

		/**
		 * All postal codes represented in the current set of MLS data are available from this method. The output can be
		 * filtered using additional GET parameters.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getPostalcodes Documentation.
		 * @param  string       $idx_id        Format: x000.
		 * @param  string       $filter_field  The field to use when filtering output.
		 *                                     Allowed values: "id, stateAbrv, mlsPtID".
		 * @param  string       $filter_value  The value by which to filter. Conditional on use of filterField.
		 * @param  string|array $rf            String or array of fields to return in the output.
		 * @return array                       Available postalcodes along with applicable property type and state as well
		 *                                     as a count of the number of occurrences of each value.
		 */
		public function get_mls_postalcodes( $idx_id, $filter_field = '', $filter_value = '', $rf = '' ) {
			$route = "mls/postalcodes/$idx_id";
			$args = array(
				'filterField' => $filter_field,
				'filterValue' => $filter_value,
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * The sum total of properties listed in a given MLS as well as sums for each property type in the MLS.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getPrices Documentation.
		 * @param  string       $idx_id        Format: x000.
		 * @param  string|array $rf            String or array of fields to return in the output.
		 * @return array                       A multidimensional array with the total sum and the sum for each property type.
		 */
		public function get_mls_prices( $idx_id, $rf = '' ) {
			$route = "mls/prices/$idx_id";
			$args = array(
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Gives a total number of listings available for a given city, county, or zipcode.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getPropertycount Documentation.
		 * @param  string $idx_id           Format: x000.
		 * @param  string $count_type       Specify if you are looking for the count of a city, county, or zipcode.
		 *                                  Allowed values: "city", "county", "zipcode".
		 * @param  int    $count_specifier  The numeric city ID, county ID, or zipcode for which you want to get a property count.
		 * @return int                      An integer count of the number of properties.
		 */
		public function get_mls_propertycount( $idx_id, $count_type = '', $count_specifier = '' ) {
			$route = "mls/propertycount/$idx_id";
			$args = array(
				'countType' => $count_type,
				'countSpecifier' => $count_specifier,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Gives the property type information for all types that are available on a given MLS.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getPropertytypes Documentation.
		 * @param  string       $idx_id        Format: x000.
		 * @param  string       $filter_field  The field to use when filtering output.
		 *                                     Allowed values: "mlsPtID, mlsPropertyType".
		 * @param  string       $filter_value  The value by which to filter. Conditional on use of filterField.
		 * @param  string|array $rf            String or array of fields to return in the output.
		 * @return array                       An array of property type information including MLS property type ID, MLS
		 *                                     property type name, parent property type, and subtypes.
		 */
		public function get_mls_propertytypes( $idx_id, $filter_field = '', $filter_value = '', $rf = '' ) {
			$route = "mls/propertytypes/$idx_id";
			$args = array(
				'filterField' => $filter_field,
				'filterValue' => $filter_value,
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * All the fields in a given MLS that are currently allowed to be searched according to MLS guidelines.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getSearchfields Documentation.
		 * @param  string       $idx_id        Format: x000.
		 * @param  string       $filter_field  The field to use when filtering output.
		 *                                     Allowed values: "mlsPtID, parentPtID".
		 * @param  string       $filter_value  The value by which to filter. Conditional on use of filterField.
		 * @param  string|array $rf            String or array of fields to return in the output.
		 * @return array                       An array containing all MLS fields that are searchable according to MLS rules
		 *                                     and IDX guidelines. Array contains the field's name (which is the field to
		 *                                     be used as a key when performing a search), the display name (as should be
		 *                                     displayed in a search form), and both the mlsPtID and parentPtID to which
		 *                                     the field belongs.
		 */
		public function get_mls_searchfields( $idx_id, $filter_field = '', $filter_value = '', $rf = '' ) {
			$route = "mls/searchfields/$idx_id";
			$args = array(
				'filterField' => $filter_field,
				'filterValue' => $filter_value,
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Field values in a given MLS that are currently allowed to be searched according to MLS guidelines.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getSearchfieldvalues Documentation.
		 * @param  string $idx_id     Format: x000.
		 * @param  int    $mls_pt_id  The IDX assigned ID of the MLS property type(s). See the propertytypes method in this
		 *                            API/Component for a lookup of property type IDs.
		 * @param  string $name       Mls field name - the IDX assigned name of the MLS field name. See the searchfields for
		 *                            the list of searchable fields.
		 * @return array              An array containing all the values for the given mls field.
		 */
		public function get_mls_searchfieldvalues( $idx_id, $mls_pt_id, $name ) {
			$route = "mls/searchfieldvalues/$idx_id";
			$args = array(
				'mlsPtID' => $mls_pt_id,
				'name' => $name,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * All zip codes represented in the current set of MLS data are available from this method. The output can be
		 * filtered using additional GET parameters.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-MLS-getZipcodes Documentation.
		 * @param  string       $idx_id        Format: x000.
		 * @param  string       $filter_field  The field to use when filtering output.
		 *                                     Allowed values: "mlsPtID, parentPtID".
		 * @param  string       $filter_value  The value by which to filter. Conditional on use of filterField.
		 * @param  string|array $rf            String or array of fields to return in the output.
		 * @return array                       Available zipcodes along with applicable property type and state as well as
		 *                                     a count of the number of occurrences of each value.
		 */
		public function get_mls_zipcodes( $idx_id, $filter_field = '', $filter_value = '', $rf = '' ) {
			$route = "mls/zipcodes/$idx_id";
			$args = array(
				'filterField' => $filter_field,
				'filterValue' => $filter_value,
				'rf' => $rf,
			);
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}


		/*
		 -------------------------------------------------------------------------------------------------------------
		 ------------------------------------------- Leads Endpoints -------------------------------------------------
		 -------------------------------------------------------------------------------------------------------------
		 */


		/**
		 * Update leads in batches of up to 100 per request.
		 *
		 * Note: Each lead field should be passed as an indexed array starting at and going to, at most, 100. There must not
		 *       be any gaps. LeadID is required for each lead to be updated
		 *
		 * Data Example:
		 * $data = array(
		 *  'id[0]' = 1,
		 *  'firstName[0]' => 'John',
		 *  'lastName[0]' => 'Doe',
		 *  'email[0]' => 'john@example.com',
		 *  'id[1]' = 2,
		 *  'firstName[1]' => 'Aaron',
		 *  'lastName[1]' => 'Aaronson',
		 *  'email[1]' => 'aaron@example.com'
		 * );
		 *
		 * @api POST
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-postBulklead Documentation.
		 * @param  array $data  Supplemental fields to update.
		 * @return mixed        If a leads are successfully updated the updated lead IDs will be returned. If no POST
		 *                      data is supplied then a list of updatable fields with format information is returned.
		 */
		public function post_bulkleads( $data = array() ) {
			$fields['method'] = 'POST';
			$fields['body'] = $data;
			$route = 'leads/bulklead';

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Add leads in batches of up to 100 per request.
		 *
		 * Note: Each lead field should be passed as an indexed array starting at and going to, at most, 100. There must not
		 *       be any gaps.
		 *
		 * Data Example:
		 * $data = array(
		 *  'firstName[0]' => 'John',
		 *  'lastName[0]' => 'Doe',
		 *  'email[0]' => 'john@example.com',
		 *  'firstName[1]' => 'Aaron',
		 *  'lastName[1]' => 'Aaronson',
		 *  'email[1]' => 'aaron@example.com'
		 * );
		 *
		 * @api PUT
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-getBulklead Documentation.
		 * @param  array $data  Supplemental fields to update.
		 * @return array        If a lead is successfully created the new lead IDs will be returned. If no PUT data is
		 *                      supplied then a list of updatable fields with format information is returned.
		 */
		public function put_bulkleads( $data = array() ) {
			$fields['method'] = 'PUT';
			$fields['body'] = $data;
			$route = 'leads/bulklead';

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Remove a lead system wide.
		 *
		 * This method is to be used at your own risk. We will NOT be held accountable for programmatic errors in your code
		 * or the improper use of search values or options within said values resulting in deletion of leads.
		 *
		 * @api DELETE
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-deleteLead Documentation.
		 * @param  int $lead_id  The ID of a lead.
		 * @return mixed         Nothing on success.
		 */
		public function delete_lead( $lead_id ) {
			$fields['method'] = 'DELETE';
			$route = "leads/lead/$lead_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Get information for one or multiple leads.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-getlead Documentation.
		 * @param  string $lead_id If no ID is given a list of IDs is returned.
		 * @param  array  $args    Query args to send in to API call.
		 * @return array           If a lead ID is provided detailed information about that lead is returned. Otherwise
		 *                         simple information about all leads is returned.
		 */
		public function get_leads( $lead_id = '', $args = array() ) {
			$route = ( '' === $lead_id ) ? 'leads/lead' : "leads/lead/$lead_id";
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Update the information for one lead specified by the primary request ID.
		 *
		 * Data Example:
		 * $data = array(
		 *  'firstName' => 'John',
		 *  'lastName' => 'Doe',
		 *  'email' => 'john@example.com'
		 * );
		 *
		 * @api POST
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-postLead Documentation.
		 * @param  int   $lead_id  The ID of a lead.
		 * @param  array $data     Lead fields to update.
		 * @return mixed           If a leads are successfully updated the updated lead IDs will be returned. If no POST
		 *                         data is supplied then a list of updatable fields with format information is returned.
		 */
		public function post_lead( $lead_id, $data ) {
			$fields['method'] = 'POST';
			$fields['body'] = $data;
			$route = "leads/leads/$lead_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Create a new lead.
		 *
		 * Special Note: Currently the API cannot differentiate between a lead rejected due to server error or one rejected
		 *               due to bad email address. The lead system requires email addresses that are correctly formatted to
		 *               cut down on garbage accounts, and they need to have a valid MX record. Most 500 error from this
		 *               method are a result of bad email addresses. In future versions we will differentiate the error and
		 *               make the MX record requirement optional.
		 *
		 * Data Example:
		 * $data = array(
		 *  'firstName' => 'John',
		 *  'lastName' => 'Doe',
		 *  'email' => 'john@example.com'
		 * );
		 *
		 * @api PUT
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-putLead Documentation.
		 * @param  array $data  Lead fields to create.
		 * @return mixed        If a lead is successfully created the new lead's ID will be returned. If no PUT data is
		 *                      supplied then a list of updatable fields with format information is returned.
		 */
		public function put_lead( $data = array() ) {
			$fields['method'] = 'PUT';
			$fields['body'] = $data;
			$route = 'leads/leads';

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Get traffic history for a specified lead.
		 *
		 * For bandwidth and memory considerations there is a limit of 5,000 on the number of lead traffics that can be
		 * returned in any single request.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-getLeadtraffic Documentation.
		 * @param  string $lead_id If no ID is given a list of IDs is returned.
		 * @param  array  $args    Query args to send in to API call.
		 * @return array           The applicable client account ID, date, lead ID, IP , page, and referrer.
		 */
		public function get_leadtraffic( $lead_id, $args = array() ) {
			$route = "leads/leadtraffic/$lead_id";
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * This is a simple, access anywhere, method for getting a list of all API components available.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-getListcomponents Documentation.
		 * @return array   All available APIs/Components.
		 */
		public function get_leads_listcomponents() {
			return $this->build_request( 'leads/listcomponents' )->request();
		}

		/**
		 * This is a simple, access anywhere, method for getting a list of all API components available.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-listmethods Documentation.
		 * @return array   Basic information about all available methods in this API.
		 */
		public function get_leads_listmethods() {
			return $this->build_request( 'leads/listmethods' )->request();
		}

		/**
		 * Remove a lead note.
		 *
		 * @api DELETE
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-deleteNote Documentation.
		 * @param  int $lead_id  The ID of a lead.
		 * @param  int $note_id  The ID of the note to delete.
		 * @return mixed         Nothing on success.
		 */
		public function delete_leads_note( $lead_id, $note_id ) {
			$fields['method'] = 'DELETE';
			$route = "leads/note/$lead_id/$note_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Get notes for a lead.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-getNote Documentation.
		 * @param  int   $lead_id  The ID of a lead.
		 * @param  int   $note_id  The ID of the note to delete.
		 * @param  array $args     Query args to send in to API call.
		 * @return array           Lead note information. If no note ID is sent all notes for the lead are returned. If a
		 *                         note ID is passed only the one note is returned.
		 */
		public function get_leads_note( $lead_id, $note_id = '', $args = array() ) {
			$route = ( '' === $note_id ) ? "leads/note/$lead_id" : "leads/note/$lead_id/$note_id";
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Update the notes information for one lead specified by the primary request ID.
		 *
		 * Data Example:
		 * $data = array(
		 *  'note' => 'Test note'
		 * );
		 *
		 * @api POST
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-postNote Documentation.
		 * @param  int   $lead_id  The ID of a lead.
		 * @param  int   $note_id  The ID of the note to update.
		 * @param  array $data     Note data.
		 * @return mixed           If no data is supplied then a list of updatable fields with format information is returned.
		 */
		public function post_leads_note( $lead_id, $note_id, $data = array() ) {
			$fields['method'] = 'POST';
			$fields['body'] = $data;
			$route = "leads/note/$lead_id/$note_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Create a new lead note.
		 *
		 * Data Example:
		 * $data = array(
		 *  'note' => 'Test note'
		 * );
		 *
		 * @api POST
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-putNote Documentation.
		 * @param  int   $lead_id  The ID of a lead.
		 * @param  array $data     Note data.
		 * @return mixed           If a note is successfully created the new notes's ID will be returned. If no PUT data is
		 *                         supplied then a list of updatable fields with format information is returned.
		 */
		public function put_leads_note( $lead_id, $data = array() ) {
			$fields['method'] = 'PUT';
			$fields['body'] = $data;
			$route = "leads/note/$lead_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Remove a lead saved property.
		 *
		 * @api DELETE
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-deleteProperty Documentation.
		 * @param  int $lead_id      The ID of a lead.
		 * @param  int $property_id  The ID of a property to delete.
		 * @return mixed             Nothing on success.
		 */
		public function delete_leads_property( $lead_id, $property_id ) {
			$fields['method'] = 'DELETE';
			$route = "leads/property/$lead_id/$property_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Get saved properties for a lead.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-getProperty Documentation.
		 * @param  int   $lead_id      The ID of a lead.
		 * @param  int   $property_id  The ID of a lead's saved property.
		 * @param  array $args         Query args to send in to API call.
		 * @return array               If no property ID is passed all properties are returned. If a property ID is passed
		 *                             only the information for that specified property is returned.
		 */
		public function get_leads_property( $lead_id, $property_id = '', $args = array() ) {
			$route = ( '' === $property_id ) ? "leads/property/$lead_id" : "leads/property/$lead_id/$property_id";
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Update an existing lead's saved property.
		 *
		 * Data Example:
		 *
		 * $data = array(
		 *  'propertyName' => 'Test Property',
		 *  'property' => array('idxID' => 'a001', 'listingID' => '345678')
		 * );
		 *
		 * @api POST
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-postProperty Documentation.
		 * @param  int   $lead_id      The ID of a lead.
		 * @param  int   $property_id  The ID of the note to update.
		 * @param  array $data         Property data.
		 * @return mixed               If no data is supplied then a list of updatable fields with format information is returned.
		 */
		public function post_leads_property( $lead_id, $property_id, $data = array() ) {
			$fields['method'] = 'POST';
			$fields['body'] = $data;
			$route = "leads/property/$lead_id/$property_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Create a new lead saved property.
		 *
		 * Data Example:
		 *
		 * $data = array(
		 *  'propertyName' => 'Test Property',
		 *  'property' => array('idxID' => 'a001', 'listingID' => '345678')
		 * );
		 *
		 * @api PUT
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-putProperty Documentation.
		 * @param  int   $lead_id   The ID of a lead.
		 * @param  array $data      Property data.
		 * @return mixed            If a saved property is successfully created the new property's ID will be returned.
		 *                          If no data is supplied then a list of updatable fields with format information is
		 *                          returned.
		 */
		public function put_leads_property( $lead_id, $data = array() ) {
			$fields['method'] = 'PUT';
			$fields['body'] = $data;
			$route = "leads/property/$lead_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Remove a lead saved search.
		 *
		 * @api DELETE
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-deleteSearch Documentation.
		 * @param  int $lead_id    The ID of a lead.
		 * @param  int $search_id  The ID of a saved search to delete.
		 * @return mixed           Nothing on success.
		 */
		public function delete_leads_search( $lead_id, $search_id ) {
			$fields['method'] = 'DELETE';
			$route = "leads/property/$lead_id/$search_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Get searches for a lead.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-getSearch Documentation.
		 * @param  int   $lead_id    The ID of a lead.
		 * @param  int   $search_id  The ID of a lead's search.
		 * @param  array $args       Query args to send in to API call.
		 * @return array             An array with 2 keys. The key searchInformation that contains all existing saved search
		 *                           information. The key info will return messages about any returned saved search. Currently
		 *                           this info will tell you if any search's advanced fields are not valid in the IDX system.
		 */
		public function get_leads_search( $lead_id, $search_id = '', $args = array() ) {
			$route = ( '' === $search_id ) ? "leads/property/$lead_id" : "leads/property/$lead_id/$search_id";
			$route = add_query_arg( $args, $route );

			return $this->build_request( $route )->request();
		}

		/**
		 * Update an existing lead's saved search.
		 *
		 * Data Example:
		 *
		 * $data = array(
		 *  'searchName' => 'Test Search',
		 *  'search' => array('idxID' => 'a001', 'hp' => '200000')
		 * );
		 *
		 * @api POST
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-postSearch Documentation.
		 * @param  int   $lead_id    The ID of a lead.
		 * @param  int   $search_id  The ID of a lead's saved search.
		 * @param  array $data       Search data.
		 * @return mixed             If a lead search is successfully created the new searches' ID will be returned. If no
		 *                           data is supplied then a list of updatable fields with format information is returned.
		 */
		public function post_leads_search( $lead_id, $search_id, $data = array() ) {
			$fields['method'] = 'POST';
			$fields['body'] = $data;
			$route = "leads/search/$lead_id/$search_id";

			return $this->build_request( $route, $fields )->request();
		}

		/**
		 * Create a new lead saved search.
		 *
		 * Data Example:
		 *
		 * $data = array(
		 *  'searchName' => 'Test Search',
		 *  'search' => array('idxID' => 'a001', 'hp' => '200000')
		 * );
		 *
		 * @api PUT
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Leads-putSearch Documentation.
		 * @param  int   $lead_id    The ID of a lead.
		 * @param  array $data       Search data.
		 * @return mixed             If a lead search is successfully created the new searches' ID will be returned. If no
		 *                           data is supplied then a list of updatable fields with format information is returned.
		 */
		public function put_leads_search( $lead_id, $data = array() ) {
			$fields['method'] = 'PUT';
			$fields['body'] = $data;
			$route = "leads/search/$lead_id";

			return $this->build_request( $route, $fields )->request();
		}

		/*
		 -------------------------------------------------------------------------------------------------------------
		 ------------------------------------- Specialty Partner Endpoints -------------------------------------------
		 -------------------------------------------------------------------------------------------------------------
		 */

		/**
		 * Get IDX account and agent/office add-on pricing.
		 *
		 * Note: This method is only available for specialty billing partners.
		 *
		 * @api GET
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Specialty_Partner-getPricing Documentation.
		 * @return array  IDX account and agent/office add-on pricing.
		 */
		public function get_specialtypartner_pricing() {
			return $this->build_request( 'specialtypartner/pricing' )->request();
		}

		/**
		 * Create IDX subscriber.
		 *
		 * Note: this method is only available for specialty billing partners.
		 *
		 * Data Example:
		 *
		 * $data = array(
		 *  'product'               => 'lite',
		 *  'firstName'             => 'Test',
		 *  'lastName'              => 'Test',
		 *  'companyName'           => 'Test Company',
		 *  'address'               => '1000 E Test street',
		 *  'city'                  => 'Eugene',
		 *  'state'                 => 'OR', // Use XX for international.
		 *  'zipcode'               => 97402,
		 *  'primaryPhone'          => '5555555555',
		 *  'email'                 => 'test@gmail.com',
		 *  'mlsIDList'             => 'a001,a002',
		 *  'agreeToTermsOfService' => 'yes'
		 * );
		 *
		 * @api PUT
		 * @see http://middleware.idxbroker.com/docs/api/methods/index.html#api-Specialty_Partner-putSubscriber Documentation.
		 * @param  array $data   Subscriber data.
		 * @return mixed         Nothing on success
		 */
		public function put_specialtypartner_subscriber( $data = array() ) {
			$fields['method'] = 'PUT';
			$fields['body'] = $data;
			$route = 'specialtypartner/subscriber';

			return $this->build_request( $route, $fields )->request();
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
