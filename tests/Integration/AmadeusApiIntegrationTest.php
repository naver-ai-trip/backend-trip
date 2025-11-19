<?php

namespace Tests\Integration;

use App\Services\Amadeus\HotelService;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Amadeus API Integration Tests - Real API Calls
 *
 * These tests are intended to hit the real Amadeus Travel APIs to verify:
 * - Credentials are working
 * - OAuth token negotiation succeeds
 * - Core hotel search endpoints return real data
 *
 * ⚠️ NOTE: These tests should be run manually with `--group=integration`
 * to avoid consuming API quota in normal CI runs.
 */
class AmadeusApiIntegrationTest extends TestCase
{
    private function resolveHotelService(): HotelService
    {
        $service = new HotelService();

        if (!$service->isEnabled()) {
            $this->markTestSkipped('Amadeus Hotel service is not enabled or credentials are missing.');
        }

        return $service;
    }

    #[Test]
    #[Group('integration')]
    #[Group('amadeus')]
    public function it_can_search_hotels_by_city(): void
    {
        $service = $this->resolveHotelService();

        $results = $service->searchHotelsByCity('NYC');

        $this->assertIsArray($results);
        $this->assertNotEmpty($results, 'Expected at least one hotel for NYC search.');

        $hotel = $results[0];
        $this->assertArrayHasKey('hotelId', $hotel);
        $this->assertArrayHasKey('name', $hotel);
        $this->assertArrayHasKey('geoCode', $hotel);

        dump([
            '✅ Amadeus Hotel Search (City)' => 'SUCCESS',
            'input' => 'NYC',
            'returned' => count($results) . ' hotels',
            'sample' => array_slice($results, 0, 3),
        ]);
    }

    #[Test]
    #[Group('integration')]
    #[Group('amadeus')]
    public function it_can_search_hotels_by_geocode(): void
    {
        $service = $this->resolveHotelService();

        $results = $service->searchHotelsByGeocode(48.8566, 2.3522, 5, 'KM'); // Paris

        $this->assertIsArray($results);
        $this->assertNotEmpty($results, 'Expected hotels near Paris geocode.');

        $hotel = $results[0];
        $this->assertArrayHasKey('hotelId', $hotel);
        $this->assertArrayHasKey('name', $hotel);
        $this->assertArrayHasKey('geoCode', $hotel);

        dump([
            '✅ Amadeus Hotel Search (Geocode)' => 'SUCCESS',
            'input' => ['lat' => 48.8566, 'lng' => 2.3522],
            'radius_km' => 5,
            'returned' => count($results) . ' hotels',
            'sample' => array_slice($results, 0, 3),
        ]);
    }

    #[Test]
    #[Group('integration')]
    #[Group('amadeus')]
    public function it_can_fetch_offers_and_offer_details_for_real_hotels(): void
    {
        $service = $this->resolveHotelService();

        $cityHotels = $service->searchHotelsByCity('NYC');
        $this->assertNotEmpty($cityHotels, 'Expected hotels to fetch offers for.');

        $hotelIds = array_values(array_filter(array_column($cityHotels, 'hotelId')));
        $this->assertNotEmpty($hotelIds, 'City search should yield hotel IDs.');

        $checkIn = now()->format('Y-m-d');
        $checkOut = now()->addDays(1)->format('Y-m-d');
        dump($checkIn, $checkOut);

        $offers = $service->getHotelOffers(
            array_slice($hotelIds, 0, 2),
            $checkIn,
            $checkOut,
            [
                'adults' => 1,
                'roomQuantity' => 1,
                'currency' => 'USD',
            ]
        );

        $this->assertIsArray($offers);
        $this->assertNotEmpty($offers, 'Expected at least one hotel offer.');

        $hotelWithOffers = $offers[0];
        $this->assertArrayHasKey('offers', $hotelWithOffers);
        $this->assertNotEmpty($hotelWithOffers['offers'], 'Expected at least one offer for hotel.');

        $offerId = $hotelWithOffers['offers'][0]['id'] ?? null;
        $this->assertNotEmpty($offerId, 'Offer response should include an offer id.');

        $offerDetails = $service->getHotelOfferById($offerId);

        $this->assertIsArray($offerDetails);
        $this->assertArrayHasKey('offers', $offerDetails);
        $this->assertNotEmpty($offerDetails['offers'], 'Expected at least one offer detail.');
        $this->assertEquals($offerId, $offerDetails['offers'][0]['id']);
        $this->assertArrayHasKey('price', $offerDetails['offers'][0]);

        dump([
            '✅ Amadeus Hotel Offers' => 'SUCCESS',
            'check_in' => $checkIn,
            'check_out' => $checkOut,
            'hotel_ids' => array_slice($hotelIds, 0, 2),
            'sample_offer' => [
                'offer_id' => $offerId,
                'total' => $offerDetails['price']['total'] ?? null,
                'currency' => $offerDetails['price']['currency'] ?? null,
            ],
        ]);
    }
}
