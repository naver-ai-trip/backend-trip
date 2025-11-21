<?php

namespace App\Services\Amadeus;

use Illuminate\Support\Facades\Log;

/**
 * FlightService
 *
 * Provides access to the Amadeus Flight Offers Search API.
 * Documentation: https://developers.amadeus.com/self-service/category/flights/api-doc/flight-offers-search/api-reference
 */
class FlightService extends AmadeusService
{
    public function __construct()
    {
        parent::__construct(config('services.amadeus', []));
    }

    /**
     * Search flight offers.
     *
     * @param  array  $parameters Query parameters expected by Amadeus
     * @return array|null Flight offers or null if request failed/disabled
     */
    public function searchFlightOffers(array $parameters): ?array
    {
        if (!$this->isEnabled()) {
            Log::warning('Amadeus Flight service is disabled');
            return null;
        }

        $client = $this->client('v2');
        if (!$client) {
            return null;
        }

        $this->logApiCall('GET', '/shopping/flight-offers', $parameters);

        try {
            $response = $client->get('/shopping/flight-offers', $parameters);
            $data = $this->handleResponse($response, 'Flight Offers Search');

            return $data['data'] ?? [];
        } catch (\Exception $e) {
            Log::error('Amadeus Flight Offers Exception', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
