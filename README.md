# wp-idxbroker-api

A WordPress php library for interacting with the [IDX Broker API](https://middleware.idxbroker.com/docs/api/overview.php).

[![Code Climate](https://codeclimate.com/repos/57d32c751a166e18a60006aa/badges/88dbe05ca5942d204761/gpa.svg)](https://codeclimate.com/repos/57d32c751a166e18a60006aa/feed)
[![Test Coverage](https://codeclimate.com/repos/57d32c751a166e18a60006aa/badges/88dbe05ca5942d204761/coverage.svg)](https://codeclimate.com/repos/57d32c751a166e18a60006aa/coverage)
[![Issue Count](https://codeclimate.com/repos/57d32c751a166e18a60006aa/badges/88dbe05ca5942d204761/issue_count.svg)](https://codeclimate.com/repos/57d32c751a166e18a60006aa/feed)
[![Build Status](https://travis-ci.org/wp-api-libraries/wp-idxbroker-api.svg?branch=master)](https://travis-ci.org/wp-api-libraries/wp-idxbroker-api)

# Example usage
Visit the [PHPDocs](https://wp-api-libraries.github.io/wp-idxbroker-api/classes/IdxBrokerAPI.html) for the full library documentation.
### GET Request
```php
$idx_api = new IdxBrokerAPI( 'example_api_key');

$results = $idx_api->build_request( 'clients/featured' )->request();
```

### POST Request
```php
$fields['method'] = 'POST';
$fields['body'] = array(
	'dynamicURL' => 'https://example.com/luxury-real-estate',
	'savedLinkID' => 'xxxxxx'
);

$results = $idx_api->build_request( 'clients/dynamicwrapperurl', $fields )->request();
```

### Check API Key Usage
After you make a call to the API you can check your hourly API key usage using the check_usage method
```php
$usage = $idx_api->check_usage();
```
