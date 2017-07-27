<?php
/**
 * IDX Broker API
 *
 * @see http://middleware.idxbroker.com/docs/api/methods/index.html API Documentation
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
			$route = add_query_arg( array( 'rf' => $rf ), $route );

			return $this->build_request( $route )->request();
		}


		/* --------------------------------------------- Client Endpoints ----------------------------------------------- */


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
			// Prepare request.
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
			// Prepare request.
			$route = ('' === $list_id ) ? 'clients/cities' : "clients/cities/$list_id";
			$route = add_query_arg( array( 'rf' => $rf ), $route );

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
			// Prepare request.
			$route = ('' === $list_id ) ? 'clients/counties' : "clients/counties/$list_id";
			$route = add_query_arg( array( 'rf' => $rf ), $route );

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
		 * @return void                 No data returned on success.
		 */
		public function post_clients_dynamicwrapperurl( $dynamic_url, $savedlink_id = null, $page_id = null ) {
			// Prepare request.
			$fields['method'] = 'POST';
			$fields['body']['dynamicURL'] = $dynamic_url;

			if ( null !== $savedlink_id ) {
				$fields['body']['savedLinkID'] = $savedlink_id;
			}
			if ( null !== $page_id ) {
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
			// Prepare request.
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
			// Prepare request.
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
		 * @param  bool   $disclaimers Include MLS disclaimer/courtesy in the response. Default true.
		 * @return array               List of fields that are returnable for the listingID.
		 */
		public function get_clients_listing( $idx_id, $listing_id, $rf = '', $disclaimers = true ) {
			// Prepare request.
			$route = "clients/listing/$idx_id/$listing_id";
			$route = add_query_arg( array( 'rf' => $rf, 'disclaimers' => $disclaimers ), $route );

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


		public function get_clients_offices() {
		}
		public function get_clients_postalcodes() {
		}
		public function get_clients_postalcodeslistname() {
		}
		public function get_clients_properties() {
		}
		public function delete_clients_savedlink() {
		}
		public function get_clients_savedlinks() {
		}
		public function post_clients_savedlink() {
		}
		public function put_clients_savedlink() {
		}
		public function get_clients_searchquery() {
		}
		public function get_clients_soldpending() {
		}
		public function delete_clients_supplemental() {
		}
		public function get_clients_supplemental() {
		}
		public function post_clients_supplemental() {
		}
		public function put_clients_supplemental() {
		}
		public function get_clients_systemlinks() {
		}
		public function get_clients_widgets() {
		}
		public function delete_clients_wrappercache() {
		}
		public function get_clients_zipcodes() {
		}

		/* MLS Endpoints. */

		/**
		 * [get_mls_age description]
		 *
		 * @return [type] [description]
		 */
		public function get_mls_age() {
		}
		public function get_mls_approvedmls() {
		}
		public function get_mls_cities() {
		}
		public function get_mls_counties() {
		}
		public function get_mls_listcomponents() {
		}
		public function get_mls_listmethods() {
		}
		public function get_mls_postalcodes() {
		}
		public function get_mls_prices() {
		}
		public function get_mls_propertycount() {
		}
		public function get_mls_propertytypes() {
		}
		public function get_mls_searchfields() {
		}
		public function get_mls_searchfieldvalues() {
		}
		public function get_mls_zipcodes() {
		}

		/* Leads Endpoints. */

		/**
		 * [add_bulk_leads description]
		 */
		public function post_bulkleads() {
		}
		public function put_bulkleads() {
		}
		public function delete_lead() {
		}
		public function get_lead() {
		}
		public function post_lead() {
		}
		public function put_lead() {
		}
		public function get_leadtraffic() {
		}
		public function get_leads_listcomponents() {
		}
		public function get_leads_listmethods() {
		}
		public function delete_leads_note() {
		}
		public function post_leads_note() {
		}
		public function put_leads_note() {
		}
		public function get_leads_note() {
		}
		public function delete_leads_property() {
		}
		public function post_leads_property() {
		}
		public function put_leads_property() {
		}
		public function get_leads_property() {
		}
		public function delete_leads_search() {
		}
		public function post_leads_search() {
		}
		public function put_leads_search() {
		}
		public function get_leads_search() {
		}

		/* Specialty Partner Endpoints. */

		/**
		 * [get_specialty_partner_pricing description]
		 *
		 * @return [type] [description]
		 */
		public function get_specialtypartner_pricing() {
		}
		public function put_specialtypartner_subscriber() {
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
