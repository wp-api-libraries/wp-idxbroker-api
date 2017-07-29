<?php
/**
 * IDX Broker API with caching.
 *
 * @package WP-API-Libraries\WP-IDX-Broker-API\Examples
 * @author sfgarza
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Optimized IdxBrokerAPI class.
 */
class _IdxBrokerAPI extends IdxBrokerAPI {


	/**
	 * By overriding the request method we can intercept the API call and choose to either make a fresh API call or
	 * retrieve a cached result from the database.
	 *
	 * @param  bool $force_refresh Force a fresh API call. Defaults to false.
	 * @return mixed               API call results.
	 */
	public function request( $force_refresh = false ) {
		// Only cache GET requests.
		if ( 'GET' !== $this->args['method'] ) {
			return parent::request();
		}

		// We md5 the route to create a unique key for that call and to also reduce the risk of
		// creating a key that is over 64 characters long, aka the max length for option names.
		$transient_key = 'idxbroker_cache_' . md5( $this->route );

		// Check if cache for API call exists.
		$request = get_transient( $transient_key );

		// If nothing is found, make the request.
		if ( true === $force_refresh || false === $request ) {
			// Parent method handles the request to IDX Broker.
			$request = parent::request();

			if ( false !== $request ) {
				set_transient( $transient_key, $request, 2 * HOUR_IN_SECONDS );
			}
		}

		return $request;
	}

}
