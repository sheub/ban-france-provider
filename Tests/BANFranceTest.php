<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

/**
 * @author Sébastien Barré <sebastien@sheub.eu>
 */

namespace Geocoder\Provider\BANFrance\Tests;

use Geocoder\IntegrationTest\BaseTestCase;
use Geocoder\IntegrationTest\CachedResponseClient;
use Geocoder\Location;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\Provider\BANFrance\BANFrance;
use Http\Client\Curl\Client as HttplugClient;

class BANFranceTest extends BaseTestCase
{
    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    /**
     * Get a real HTTP client. If a cache dir is set to a path it will use cached responses.
     *
     * @return HttpClient
     */
    protected function getHttpClient($apiKey = null, $appCode = null)
    {
        if (null !== $cacheDir = $this->getCacheDir()) {
            return new CachedResponseClient(new HttplugClient(), $cacheDir, $apiKey, $appCode);
        } else {
            return new HttplugClient();
        }
    }


    public function testGeocodeWithRealAddress()
    {
        $provider = new BANFrance($this->getHttpClient());
        $query = GeocodeQuery::create('10 avenue Gambetta, Paris, France');
        $query = $query->withLimit(1);
        $query = $query->withData('postcode', 75020);
        $query = $query->withData('lat', 48.8653);
        $query = $query->withData('lon', 2.39844);
        

        $results = $provider->geocodeQuery($query, 'Geocoder PHP/BANFrance Provider/BANFrance Test');

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var Location $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals(48.8653, $result->getCoordinates()->getLatitude(), '', 0.01);
        $this->assertEquals(2.39844, $result->getCoordinates()->getLongitude(), '', 0.01);
        $this->assertEquals(10, $result->getStreetNumber());
        $this->assertEquals('Avenue Gambetta', $result->getStreetName());
        $this->assertEquals(75020, $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
    }

    public function testReverseWithRealCoordinates()
    {
        $provider = new BANFrance($this->getHttpClient());
        $results = $provider->reverseQuery(ReverseQuery::fromCoordinates(48.8632156, 2.3887722));

        $this->assertInstanceOf('Geocoder\Model\AddressCollection', $results);
        $this->assertCount(1, $results);

        /** @var Location $result */
        $result = $results->first();
        $this->assertInstanceOf('\Geocoder\Model\Address', $result);
        $this->assertEquals(48.8632156, $result->getCoordinates()->getLatitude(), '', 0.0001);
        $this->assertEquals(2.3887722, $result->getCoordinates()->getLongitude(), '', 0.0001);
        $this->assertEquals(1, $result->getStreetNumber());
        $this->assertEquals('Avenue Gambetta', $result->getStreetName());
        $this->assertEquals(75020, $result->getPostalCode());
        $this->assertEquals('Paris', $result->getLocality());
    }

    public function testGetName()
    {
        $provider = new BANFrance($this->getMockedHttpClient(), 'Geocoder PHP/BANFrance Provider/BANFrance Test');
        $this->assertEquals('BANFrance', $provider->getName());
    }

    /**
     * @expectedException \Geocoder\Exception\InvalidServerResponse
     */
    public function testGeocodeWithInvalidData()
    {
        $provider = new BANFrance($this->getMockedHttpClient(), 'Geocoder PHP/BANFrance Provider/BANFrance Test');
        $provider->geocodeQuery(GeocodeQuery::create('foobar'));
    }

    /**
     * @expectedException \Geocoder\Exception\UnsupportedOperation
     * @expectedExceptionMessage The BANFrance provider does not support IP addresses, only street addresses.
     */
    public function testGeocodeIpv4()
    {
        $provider = new BANFrance($this->getMockedHttpClient(), 'Geocoder PHP/BANFrance Provider/BANFrance Test');
        $provider->geocodeQuery(GeocodeQuery::create('127.0.0.1'));
    }

    /**
     * @expectedException \Geocoder\Exception\UnsupportedOperation
     * @expectedExceptionMessage The BANFrance provider does not support IP addresses, only street addresses.
     */
    public function testGeocodeWithLocalhostIPv6()
    {
        $provider = new BANFrance($this->getMockedHttpClient(), 'Geocoder PHP/BANFrance Provider/BANFrance Test');
        $provider->geocodeQuery(GeocodeQuery::create('::1'));
    }


    /**
     * @expectedException \Geocoder\Exception\UnsupportedOperation
     * @expectedExceptionMessage The BANFrance provider does not support IP addresses, only street addresses.
     */
    public function testGeocodeWithRealIPv6()
    {
        $provider = new BANFrance($this->getMockedHttpClient(), 'Geocoder PHP/BANFrance Provider/BANFrance Test');
        $provider->geocodeQuery(GeocodeQuery::create('::ffff:88.188.221.14'));
    }
}
