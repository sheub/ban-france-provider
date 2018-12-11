# *Base Addresse Nationale* provider for the [geocoder-php](https://github.com/geocoder-php/Geocoder) library
[![Build Status](https://travis-ci.org/sheub/ban-france-provider.svg?branch=master)](https://travis-ci.org/sheub/BANFrance-provider)
[![Quality Score](https://img.shields.io/scrutinizer/g/sheub/BANFrance-provider.svg?style=flat-square)](https://scrutinizer-ci.com/g/sheub/BANFrance-provider)
[![Latest Stable Version](https://poser.pugx.org/sheub/ban-france-provider/v/stable)](https://packagist.org/packages/sheub/ban-france-provider)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE)


[Geocoder PHP](https://github.com/geocoder-php/Geocoder) is a PHP library which helps you build geo-aware applications by providing a powerful abstraction layer for geocoding and reverse-geocoding. See the [main repo](https://github.com/geocoder-php/Geocoder) for the documentation.

This is the provider for the *Base Addresse Nationale Geocoder*. Its coverage is limited to France. Additional information on the service and the API can be found here: https://adresse.data.gouv.fr/

## Install

```bash
composer require sheub/ban-france-provider
```

## Usage

```php
use Geocoder\Query\GeocodeQuery;
use Geocoder\Provider\BANFrance\BANFrance;
use Geocoder\Model\Coordinates;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Http\Client\HttpClient;


$httpClient = new \Http\Adapter\Guzzle6\Client();
$geocoder = new BANFrance($httpClient);

// Query with unstructured address
$result = $geocoder->geocodeQuery(GeocodeQuery::create('8 Boulevard du Port 80000 Amiens'));

// Query with limit and postcode
$query = GeocodeQuery::create('8 Boulevard du Port');
$query = $query->withLimit(1);
$query = $query->withData('postcode', 80000);

$result = $provider->geocodeQuery($query);


// Reverse geocoding
$provider = new BANFrance($httpClient);
$results = $provider->reverseQuery(ReverseQuery::fromCoordinates(48.8632156, 2.3887722));

```

## Contribute

Contributions are very welcome! Send a pull request to the [main repository](https://github.com/sheub/BANFrance-provider) or 
report any issues you find on the [issue tracker](https://github.com/sheub/BANFrance-provider/issues).
