<?php
/**
 * IDX Broker API
 *
 * @package WP-IDX-Broker-API
 */
/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! class_exists( 'IdxBrokerAPI' ) ) {
/**
 * IdxBrokerAPI class.
 */
class IdxBrokerAPI {
	/**
	 * Headers
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access protected
	 */
	private $args = array( 'sslverify' => false );
	/**
	 * IDX Broker route to make a the call to
	 *
	 * @var [String]
	 */
	private $route;
	/**
	 * Raw response from IDX Broker server
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
	 * HTTP or HTTPS.
	 *
	 * @var [String]
	 */
	public $scheme;
	/**
	 * Domain
	 *
	 * @var [String] domain
	 * @access public
	 */
	public $domain;
	/**
	 * IDX results URI
	 *
	 * @var [String] results_uri
	 */
	public $results_uri;
	/**
	 * __construct function.
	 *
	 * @access public
	 * @param  [String] $api_key : IDX Broker API key.
	 * @return void
	 */
	public function __construct( $api_key = null ) {
		$idx_opts = get_option( 'idxbroker-general' );
		$idx_info = get_option( 'idxbroker-info' );
		$api_key      = $api_key ?? $idx_opts['apikey'] ?? '' ;
		$partner_key  = $idx_opts['partner_key'] ?? '';
		$this->scheme 		  = apply_filters( 'idx_subdomain_http_scheme', $idx_info['scheme'] ?? '' );
		$this->domain 			= $idx_info['domain'] ?? '';
		$this->results_uri  = $idx_info['results_uri'] ?? '';
		$this->args['headers'] = array(
		'Content-Type' => IDXBROKER_CONTENT_TYPE,
		'accesskey' => $api_key,
		'ancillarykey' => $partner_key,
		'outputtype' => IDXBROKER_OUTPUT_TYPE,
		'apiversion' => IDXBROKER_API_VERSION,
		);
	}
	/**
	 * Request function.
	 *
	 * @access public
	 * @return Results
	 */
	public function request() {
		$result = false;
		$this->response = wp_remote_request( IDXBROKER_API_URL . $this->route,  $this->args );
		$this->check_usage();
		$this->get_response_code();
		if ( 200 === $this->code ) {
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
		$hour_usage = wp_remote_retrieve_header( $this->response, 'hourly-access-key-usage' );
		update_option( 'idxbroker-api-hourly-usage', $hour_usage );
	}
	/**
	 * Gets the response code from the response.
	 */
	private function get_response_code() {
		$this->code = wp_remote_retrieve_response_code( $this->response );
		if ( WP_DEBUG && 200 !== $this->code ) {
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
				$msg = __( 'OK.','text-domain' );
				break;
			case 204:
				$msg = __( 'OK, nothing returned.','text-domain' );
				break;
			case 400:
				$msg = __( 'Required parameter missing or invalid.','text-domain' );
				break;
			case 401:
				$msg = __( 'Accesskey not valid or revoked.','text-domain' );
				break;
			case 403.4:
				$msg = __( 'URL provided is not using SSL (HTTPS).','text-domain' );
				break;
			case 404:
				$msg = __( 'Invalid API component specified.','text-domain' );
				break;
			case 405:
				$msg = __( 'Method requested is invalid. This usually indicates a typo or that you may be requested a method that is part of a different API component.','text-domain' );
				break;
			case 406:
				$msg = __( 'Accesskey not provided.','text-domain' );
				break;
			case 409:
				$msg = __( 'Duplicate unique data detected.','text-domain' );
				break;
			case 412:
				$msg = __( "Account is over it's hourly access limit.",'text-domain' );
				break;
			case 413:
				$msg = __( 'Requested entity too large.','text-domain' );
				break;
			case 416:
				$msg = __( 'Requested time range not satisfiable.','text-domain' );
				break;
			case 417:
				$msg = __( 'There are more saved links in the account than allowed through the API.','text-domain' );
				break;
			case 500:
				$msg = __( 'General system error. Please try again later or contact IDX support.','text-domain' );
				break;
			case 503:
				$msg = __( 'Scheduled or emergency API maintenance will result in 503 errors.','text-domain' );
				break;
			case 521:
				$msg = __( 'Temporary error. There is a possibility that not all API methods are affected.','text-domain' );
				break;
			default:
				$msg = __( 'Response code unknown','text-domain' );
				break;
		}
		return $msg;
	}
}
}
