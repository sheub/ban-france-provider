<?php

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\BANFrance\Tests;

use Geocoder\IntegrationTest\ProviderIntegrationTest;
use Geocoder\IntegrationTest\CachedResponseClient;
use Geocoder\Provider\BANFrance\BANFrance;
use Geocoder\Collection;
use Geocoder\Location;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Bounds;
use Geocoder\Model\Coordinates;
use Geocoder\Model\Country;
use Geocoder\Provider\Provider;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Http\Client\HttpClient;
use Http\Discovery\HttpClientDiscovery;

/**
 * @author Sébastien Barré <sebastien@sheub.eu>
 */
class IntegrationTest extends ProviderIntegrationTest
{
    protected $testIpv4 = false;

    protected $testIpv6 = false;

    protected function createProvider(HttpClient $httpClient)
    {
        return new BANFrance($httpClient, 'Geocoder PHP/BANFrance Provider/BANFrance Test');
    }

    protected function getCacheDir()
    {
        return __DIR__.'/.cached_responses';
    }

    /**
     * This client will make real request if cache was not found.
     *
     * @return CachedResponseClient
     */
    private function getCachedHttpClient()
    {
        try {
            $client = HttpClientDiscovery::find();
        } catch (\Http\Discovery\NotFoundException $e) {
            $client = $this->getMockForAbstractClass(HttpClient::class);

            $client
                ->expects($this->any())
                ->method('sendRequest')
                ->willThrowException($e);
        }

        return new CachedResponseClient(
            $client,
            $this->getCacheDir()
    );
    }


    public function testGeocodeQuery()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }
        if (!$this->testAddress) {
            $this->markTestSkipped('Geocoding address is not supported by this provider');
        }

        $provider = $this->createProvider($this->getCachedHttpClient());
        $query = GeocodeQuery::create('8 Boulevard du Port 80000 Amiens');
        $query = $query->withLimit(1);

        $result = $provider->geocodeQuery($query);
        $this->assertWellFormattedResult($result);

        // Check Downing Street
        $location = $result->first();
        $this->assertEquals(49.897446, $location->getCoordinates()->getLatitude(), 'Latitude should be in Amiens', 0.1);
        $this->assertEquals(2.29009, $location->getCoordinates()->getLongitude(), 'Longitude should be in Amiens', 0.1);
        $this->assertContains('Boulevard du Port', $location->getStreetName(), 'Street name should contain "Boulevard du Port"');

        if (null !== $streetNumber = $location->getStreetNumber()) {
            $this->assertContains('8', $streetNumber, 'Street number should contain "8"');
        }
    }

    public function testGeocodeQueryWithNoResults()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }
        if (!$this->testAddress) {
            $this->markTestSkipped('Geocoding address is not supported by this provider');
        }

        $provider = $this->createProvider($this->getCachedHttpClient());
        $query = GeocodeQuery::create('jsajhgsdkfjhsfkjhaldkadjaslgldasd');
        $result = $provider->geocodeQuery($query);
        $this->assertWellFormattedResult($result);
        $this->assertEquals(0, $result->count());
    }

    public function testReverseQuery()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }
        if (!$this->testReverse) {
            $this->markTestSkipped('Reverse geocoding address is not supported by this provider');
        }

        $provider = $this->createProvider($this->getCachedHttpClient());

        // Close to the white house
        $result = $provider->reverseQuery(ReverseQuery::fromCoordinates(2.37, 48.357));
        $this->assertWellFormattedResult($result);
    }

    public function testReverseQueryWithNoResults()
    {
        if (isset($this->skippedTests[__FUNCTION__])) {
            $this->markTestSkipped($this->skippedTests[__FUNCTION__]);
        }

        if (!$this->testReverse) {
            $this->markTestSkipped('Reverse geocoding address is not supported by this provider');
        }

        $provider = $this->createProvider($this->getCachedHttpClient());

        $result = $provider->reverseQuery(ReverseQuery::fromCoordinates(0, 52.52));
        $this->assertEquals(0, $result->count());
    }

    /**
     * Make sure that a result for a Geocoder is well formatted. Be aware that even
     * a Location with no data may be well formatted.
     *
     * @param $result
     */
    private function assertWellFormattedResult(Collection $result)
    {
        $this->assertInstanceOf(
            Collection::class,
            $result,
            'The result must be an instance of a Geocoder\Collection'
        );

        /** @var Location $location */
        foreach ($result as $location) {
            $this->assertInstanceOf(
                Location::class,
                $location,
                'All items in Geocoder\Collection must implement Geocoder\Location'
            );

            $this->assertInstanceOf(
                AdminLevelCollection::class,
                $location->getAdminLevels(),
                'Location::getAdminLevels MUST always return a AdminLevelCollection'
            );
            $arrayData = $location->toArray();
            $this->assertTrue(is_array($arrayData), 'Location::toArray MUST return an array.');
            $this->assertNotEmpty($arrayData, 'Location::toArray cannot be empty.');

            // Verify coordinates
            if (null !== $coords = $location->getCoordinates()) {
                $this->assertInstanceOf(
                    Coordinates::class,
                    $coords,
                    'Location::getCoordinates MUST always return a Coordinates or null'
                );

                // Using "assertNotEmpty" means that we can not have test code where coordinates is on equator or long = 0
                $this->assertNotEmpty($coords->getLatitude(), 'If coordinate object exists it cannot have an empty latitude.');
                $this->assertNotEmpty($coords->getLongitude(), 'If coordinate object exists it cannot have an empty longitude.');
            }
        }
    }

    protected function getApiKey()
    {
    }
}
