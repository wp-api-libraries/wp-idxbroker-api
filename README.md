# wp-idxbroker-api

A WordPress php library for interacting with the [IDX Broker API](https://middleware.idxbroker.com/docs/api/overview.php).

[![Code Climate](https://codeclimate.com/repos/57d32c751a166e18a60006aa/badges/88dbe05ca5942d204761/gpa.svg)](https://codeclimate.com/repos/57d32c751a166e18a60006aa/feed)
[![Test Coverage](https://codeclimate.com/repos/57d32c751a166e18a60006aa/badges/88dbe05ca5942d204761/coverage.svg)](https://codeclimate.com/repos/57d32c751a166e18a60006aa/coverage)
[![Issue Count](https://codeclimate.com/repos/57d32c751a166e18a60006aa/badges/88dbe05ca5942d204761/issue_count.svg)](https://codeclimate.com/repos/57d32c751a166e18a60006aa/feed)
[![Build Status](https://travis-ci.org/wp-api-libraries/wp-idxbroker-api.svg?branch=master)](https://travis-ci.org/wp-api-libraries/wp-idxbroker-api)

# Example usage
The IDX Broker API library contains a method for each API endpoint. Visit the [PHPDocs](https://wp-api-libraries.github.io/wp-idxbroker-api/classes/IdxBrokerAPI.html) for the full library documentation.

### GET Request
```php
$idx_api = new IdxBrokerAPI( 'example_api_key');

$res1 = $idx_api->get_clients_featured();

$res2 = $idx_api->get_clients_systemlinks( 'url' );

$res3 = $idx_api->get_mls_approvedmls();
```

### POST Request
```php
$res1 = $idx_api->post_clients_dynamicwrapperurl( 'https://example.com/luxury-real-estate', '12345' );

$data = array( 'note' => 'Wheres my IDX?' );
$res2 = $idx_api->post_leads_note( '3', '1', $data );

```

### PUT Request
```php
$data = array( 
  'propertyName' => 'Test Property',
  'property' => array('idxID' => 'a001', 'listingID' => '345678')
);
$res1 = $idx_api->put_leads_property( 812, $data );
```

### DELETE Request
```php
$res1 = $idx_api->delete_leads_search( 321, 8 );
```

### Check API Key Usage
After you make a call to the API you can check your hourly API key usage using the check_usage method
```php
$usage = $idx_api->check_usage();
```
