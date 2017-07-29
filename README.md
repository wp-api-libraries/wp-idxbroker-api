# WP IDX Broker API

A WordPress php library for interacting with the [IDX Broker API](https://middleware.idxbroker.com/docs/api/overview.php).

[![Code Climate](https://codeclimate.com/repos/57d32c751a166e18a60006aa/badges/88dbe05ca5942d204761/gpa.svg)](https://codeclimate.com/repos/57d32c751a166e18a60006aa/feed)
[![Test Coverage](https://codeclimate.com/repos/57d32c751a166e18a60006aa/badges/88dbe05ca5942d204761/coverage.svg)](https://codeclimate.com/repos/57d32c751a166e18a60006aa/coverage)
[![Issue Count](https://codeclimate.com/repos/57d32c751a166e18a60006aa/badges/88dbe05ca5942d204761/issue_count.svg)](https://codeclimate.com/repos/57d32c751a166e18a60006aa/feed)
[![Build Status](https://travis-ci.org/wp-api-libraries/wp-idxbroker-api.svg?branch=master)](https://travis-ci.org/wp-api-libraries/wp-idxbroker-api)

# Example Usage
The IDX Broker API library contains a method for each endpoint of the API. Visit the [PHPDocs](https://wp-api-libraries.github.io/wp-idxbroker-api/classes/IdxBrokerAPI.html) for the full documentation.

#### GET Requests
```php
$idx_api = new IdxBrokerAPI( 'example_api_key' );

$res1 = $idx_api->get_clients_featured();

$res2 = $idx_api->get_clients_systemlinks( 'url' );

$res3 = $idx_api->get_mls_approvedmls();
```

#### POST Requests
```php
$res1 = $idx_api->post_clients_dynamicwrapperurl( 'https://example.com/luxury-real-estate', '12345' );

$data = array( 'note' => 'Wheres my IDX?' );
$res2 = $idx_api->post_leads_note( '3', '1', $data );

```

#### PUT Requests
```php
$data = array( 
  'propertyName' => 'Test Property',
  'property' => array('idxID' => 'a001', 'listingID' => '345678' )
);
$res1 = $idx_api->put_leads_property( 812, $data );
```

#### DELETE Requests
```php
$res1 = $idx_api->delete_clients_supplemental( 345678 );
```

## Helper Methods

The library also provides a few methods that assist in  extracting  information that is not readily accessible.

#### Check API Key Usage
After you make a call to the API you can check your hourly API key usage using the check_usage method
```php
$usage = $idx_api->check_usage();
```

#### Get Wrapper Domain
The API doesnt have an easy way of getting the domain used on the client wrapper pages. The domain can be some version of either `<youraccount>.idxbroker.com` or `<customsubdomain>.<yourdomain>.com`
```php
$domain = $idx_api->get_idx_domain();

/*
Results
Array
(
    [scheme] => https
    [url] => search.example.com
    [full] => https://search.example.com
)
*/
```

## Extending Functionality

The IDXBrokerAPI Class is extensible, which gives developers the ability to override the functionality of the class to their needs.

For Example. Exceeding the hourly limit is a common issue developers may face when using the API. By overriding the `request()` method, we can cache the calls made to the API.
```php
class OptimizedIdxBrokerAPI extends IdxBrokerAPI {

  /**
   * By overriding the request method we can intercept the API call and choose to 
   * either make a fresh API call or retrieve a cached result from the database.
   */
  public function request( $force_refresh = false ) {
    // Only cache GET requests.
    if ( 'GET' !== $this->args['method'] ) {
      return parent::request();
    }

    // We md5 the route to create a unique key for that call and to also reduce the risk of
    // creating a key that is over 64 characters long, aka the max length for option names.
    $transient_key = 'idxbroker_cache_' . md5( $this->route );

    // Check if cached results for API call exist.
    $request = get_transient( $transient_key );

    // If nothing is found, make the request.
    if ( true === $force_refresh || false === $request ) {
      // Parent method handles the request to IDX Broker.
      $request = parent::request();

      if ( false !== $request ) {
        set_transient( $transient_key, $request, 1 * HOUR_IN_SECONDS );
      }
    }
		
    return $request;
  }
	
}
```
Now when you instantiate your new class and make calls to methods such as `get_clients_featured()` or `get_partners_clients()`, you will get cached versions of the results if they are available.

##### Usage:
```php
$optimized_idx_api = new OptimizedIdxBrokerAPI( 'yourapikey' );

$results = $optimized_idx_api->get_clients_featured(); // Fresh call to the API.

$results = $optimized_idx_api->get_clients_featured(); // Cached results.
```
