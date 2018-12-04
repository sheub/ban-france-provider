<?php

declare(strict_types=1);

/*
 * This file is part of the Geocoder package.
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @license    MIT License
 */

namespace Geocoder\Provider\BANFrance;

use Geocoder\Collection;
use Geocoder\Exception\InvalidCredentials;
use Geocoder\Exception\UnsupportedOperation;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Query\GeocodeQuery;
use Geocoder\Query\ReverseQuery;
use Geocoder\Http\Provider\AbstractHttpProvider;
use Geocoder\Provider\Provider;
use Http\Client\HttpClient;

/**
 * @author Sébastien Barré <sebastien@sheub.eu>
 */
final class BANFrance extends AbstractHttpProvider implements Provider
{
    /**
     * @var string
     */
    const GEOCODE_ENDPOINT_URL = 'https://api-adresse.data.gouv.fr/search/?q=%s&limit=%d';

    /**
     * @var string
     */
    const REVERSE_ENDPOINT_URL = 'https://api-adresse.data.gouv.fr/reverse/?&lon=%F&lat=%F';

    /**
     * @param HttpClient $adapter An HTTP adapter.
     */
    public function __construct(HttpClient $client)
    {
        parent::__construct($client);
    }

    /**
     * {@inheritdoc}
     */
    public function geocodeQuery(GeocodeQuery $query): Collection
    {
        $address = $query->getText();
        $limit = $query->getLimit();
        // This API doesn't handle IPs
        if (filter_var($address, FILTER_VALIDATE_IP)) {
            throw new UnsupportedOperation('The BANFrance provider does not support IP addresses, only street addresses.');
        }
        // Save a request if no valid address entered
        if (empty($address)) {
            throw new InvalidArgument('Address cannot be empty.');
        }

        $url = sprintf(self::GEOCODE_ENDPOINT_URL, urlencode($query->getText()), $limit);
        return $this->executeQuery($url, $query->getLimit());
    }

    /**
     * {@inheritdoc}
     */
    public function reverseQuery(ReverseQuery $query): Collection
    {
        $coordinates = $query->getCoordinates();
        $url = sprintf(self::REVERSE_ENDPOINT_URL, $coordinates->getLongitude(), $coordinates->getLatitude());
        return $this->executeQuery($url);
    }

    /**
     * @param string $url
     * @param int    $limit
     *
     * @return \Geocoder\Collection
     */
    private function executeQuery(string $url, int $limit = null): Collection
    {
        $content = $this->getUrlContents($url);
        $json = json_decode($content, true);


        if (!isset($json['features']) || empty($json['features'])) {
            return new AddressCollection([]);
        }

        $locations = $json['features'];

        $results = [];
        foreach ($locations as $item) {
            $results[] = Address::createFromArray([
                'providedBy' => $this->getName(),
                'longitude' => $item['geometry']['coordinates'][0] ?? null,
                'latitude' => $item['geometry']['coordinates'][1] ?? null,
                'streetName' => $item['properties']['street'] ?? null,
                'streetNumber' => $item['properties']['housenumber'] ?? null,
                'locality' => $item['properties']['city'] ?? null,
                'postalCode' => $item['properties']['postcode'] ?? null,
            ]);
            if (count($results) >= $limit) {
                break;
            }
        }

        return new AddressCollection($results);
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'BANFrance';
    }
}
