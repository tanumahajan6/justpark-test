<?php

namespace Tests\Feature;

use Database\Seeders\ParkingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ParkingAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ParkingSeeder::class);
    }

    // ══════════════════════════════════════════════════════════════
    // SEARCH / AVAILABILITY TESTS
    // ══════════════════════════════════════════════════════════════

    /**
     * Basic happy path — a clean time window with no bookings should
     * return all 4 seeded spaces.
     */
    public function test_returns_all_spaces_for_an_unbooked_window(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-06-01+09:00:00&end_time=2026-06-01+12:00:00'
        );

        $response->assertStatus(200);
        $this->assertCount(4, $response->json('data'));
    }

    /**
     * Space 1 is booked 09:00–12:00 on 2026-03-01.
     * Searching the exact same window should exclude it.
     */
    public function test_excludes_space_booked_for_exact_same_window(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-03-01+09:00:00&end_time=2026-03-01+12:00:00'
        );

        $response->assertStatus(200);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertNotContains(1, $ids); // booked
        $this->assertContains(2, $ids);    // free (same location, different space)
        $this->assertContains(3, $ids);    // free
        $this->assertContains(4, $ids);    // free
    }

    /**
     * Partial overlap from the left:
     * Space 1 is booked 09:00–12:00. Searching 08:00–10:00 overlaps it.
     */
    public function test_excludes_space_with_partial_overlap_from_left(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-03-01+08:00:00&end_time=2026-03-01+10:00:00'
        );

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertNotContains(1, $ids);
    }

    /**
     * Partial overlap from the right:
     * Space 1 is booked 09:00–12:00. Searching 11:00–13:00 overlaps it.
     */
    public function test_excludes_space_with_partial_overlap_from_right(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-03-01+11:00:00&end_time=2026-03-01+13:00:00'
        );

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertNotContains(1, $ids);
    }

    /**
     * Requested window fully contains an existing booking:
     * Space 1 is booked 09:00–12:00. Searching 08:00–13:00 wraps around it.
     */
    public function test_excludes_space_when_requested_window_wraps_existing_booking(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-03-01+08:00:00&end_time=2026-03-01+13:00:00'
        );

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertNotContains(1, $ids);
    }

    /**
     * Requested window is fully inside an existing booking:
     * Space 1 is booked 09:00–12:00. Searching 10:00–11:00 sits inside it.
     */
    public function test_excludes_space_when_requested_window_is_inside_existing_booking(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-03-01+10:00:00&end_time=2026-03-01+11:00:00'
        );

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertNotContains(1, $ids);
    }

    /**
     * Adjacent window — ends exactly when the booking starts.
     * Space 1 is booked 09:00–12:00. Searching 07:00–09:00 is adjacent,
     * NOT overlapping, so space 1 should be available.
     */
    public function test_includes_space_when_window_ends_exactly_when_booking_starts(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-03-01+07:00:00&end_time=2026-03-01+09:00:00'
        );

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains(1, $ids); // adjacent — should be available
    }

    /**
     * Adjacent window — starts exactly when the booking ends.
     * Space 1 is booked 09:00–12:00. Searching 12:00–14:00 is adjacent,
     * NOT overlapping, so space 1 should be available.
     */
    public function test_includes_space_when_window_starts_exactly_when_booking_ends(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-03-01+12:00:00&end_time=2026-03-01+14:00:00'
        );

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertContains(1, $ids); // adjacent — should be available
    }

    // ── Filter: location ─────────────────────────────────────────

    /**
     * Filtering by a partial location name should only return
     * spaces whose location contains that string.
     */
    public function test_filters_spaces_by_partial_location_name(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-06-01+09:00:00&end_time=2026-06-01+12:00:00&location=City+Center'
        );

        $response->assertStatus(200);

        $locations = collect($response->json('data'))->pluck('location');
        foreach ($locations as $location) {
            $this->assertStringContainsString('City Center', $location);
        }

        // Spaces 1 and 2 are "City Center Garage A" — both should appear
        $this->assertCount(2, $response->json('data'));
    }

    /**
     * A location filter that matches nothing should return an empty list,
     * not an error.
     */
    public function test_returns_empty_list_when_location_filter_matches_nothing(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-06-01+09:00:00&end_time=2026-06-01+12:00:00&location=NonExistentPlace'
        );

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    // ── Filter: max_price ────────────────────────────────────────

    /**
     * max_price=4.00 should only return spaces at or below that rate.
     * From seed data: space 3 (£3.50) qualifies; spaces 1,2 (£5.00)
     * and 4 (£7.50) do not.
     */
    public function test_filters_spaces_by_max_price(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-06-01+09:00:00&end_time=2026-06-01+12:00:00&max_price=4.00'
        );

        $response->assertStatus(200);

        $prices = collect($response->json('data'))->pluck('hourly_price');
        foreach ($prices as $price) {
            $this->assertLessThanOrEqual(4.00, $price);
        }

        $this->assertCount(1, $response->json('data'));
        $this->assertEquals(3, $response->json('data.0.id'));
    }

    // ── Validation ───────────────────────────────────────────────

    /**
     * start_time is required — omitting it should return 422.
     */
    public function test_returns_422_when_start_time_is_missing(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?end_time=2026-06-01+12:00:00'
        );

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['start_time']);
    }

    /**
     * end_time is required — omitting it should return 422.
     */
    public function test_returns_422_when_end_time_is_missing(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-06-01+09:00:00'
        );

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['end_time']);
    }

    /**
     * end_time must be after start_time.
     */
    public function test_returns_422_when_end_time_is_before_start_time(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-06-01+12:00:00&end_time=2026-06-01+09:00:00'
        );

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['end_time']);
    }

    /**
     * Response shape — each space should have id, location, hourly_price.
     */
    public function test_response_contains_correct_fields(): void
    {
        $response = $this->getJson(
            '/api/v1/parking-spaces?start_time=2026-06-01+09:00:00&end_time=2026-06-01+12:00:00'
        );

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => ['id', 'location', 'hourly_price'],
                     ],
                 ]);
    }

    // ══════════════════════════════════════════════════════════════
    // CONFLICT DETECTION TESTS
    // ══════════════════════════════════════════════════════════════

    /**
     * Happy path — booking a space with no conflict should return 201.
     */
    public function test_creates_booking_successfully_when_no_conflict(): void
    {
        // Space 2 has no bookings in the seeder
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 2,
            'user_id'          => 1,
            'start_time'       => '2026-03-01 09:00:00',
            'end_time'         => '2026-03-01 12:00:00',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['data' => ['id', 'parking_space_id', 'user_id', 'start_time', 'end_time']])
                 ->assertJsonPath('data.parking_space_id', 2);
    }

    /**
     * Exact same window as an existing booking should return 409.
     * Space 1 is booked 09:00–12:00 on 2026-03-01 in the seeder.
     */
    public function test_returns_409_for_exact_same_window(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 1,
            'user_id'          => 1,
            'start_time'       => '2026-03-01 09:00:00',
            'end_time'         => '2026-03-01 12:00:00',
        ]);

        $response->assertStatus(409)
                 ->assertJsonFragment(['error' => 'conflict']);
    }

    /**
     * Partial overlap from the left should return 409.
     * Booking 08:00–10:00 overlaps existing 09:00–12:00.
     */
    public function test_returns_409_for_partial_overlap_from_left(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 1,
            'user_id'          => 1,
            'start_time'       => '2026-03-01 08:00:00',
            'end_time'         => '2026-03-01 10:00:00',
        ]);

        $response->assertStatus(409);
    }

    /**
     * Partial overlap from the right should return 409.
     * Booking 11:00–13:00 overlaps existing 09:00–12:00.
     */
    public function test_returns_409_for_partial_overlap_from_right(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 1,
            'user_id'          => 1,
            'start_time'       => '2026-03-01 11:00:00',
            'end_time'         => '2026-03-01 13:00:00',
        ]);

        $response->assertStatus(409);
    }

    /**
     * New booking fully wraps existing booking should return 409.
     * Booking 08:00–13:00 fully contains existing 09:00–12:00.
     */
    public function test_returns_409_when_new_booking_wraps_existing(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 1,
            'user_id'          => 1,
            'start_time'       => '2026-03-01 08:00:00',
            'end_time'         => '2026-03-01 13:00:00',
        ]);

        $response->assertStatus(409);
    }

    /**
     * New booking fully inside an existing booking should return 409.
     * Booking 10:00–11:00 sits inside existing 09:00–12:00.
     */
    public function test_returns_409_when_new_booking_is_inside_existing(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 1,
            'user_id'          => 1,
            'start_time'       => '2026-03-01 10:00:00',
            'end_time'         => '2026-03-01 11:00:00',
        ]);

        $response->assertStatus(409);
    }

    /**
     * Adjacent booking — starts exactly when existing ends.
     * Booking 12:00–14:00 is adjacent to existing 09:00–12:00.
     * Should succeed (201), not conflict.
     */
    public function test_allows_booking_that_starts_exactly_when_existing_ends(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 1,
            'user_id'          => 1,
            'start_time'       => '2026-03-01 12:00:00',
            'end_time'         => '2026-03-01 14:00:00',
        ]);

        $response->assertStatus(201);
    }

    /**
     * Adjacent booking — ends exactly when existing starts.
     * Booking 07:00–09:00 is adjacent to existing 09:00–12:00.
     * Should succeed (201), not conflict.
     */
    public function test_allows_booking_that_ends_exactly_when_existing_starts(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 1,
            'user_id'          => 1,
            'start_time'       => '2026-03-01 07:00:00',
            'end_time'         => '2026-03-01 09:00:00',
        ]);

        $response->assertStatus(201);
    }

    /**
     * Different spaces never conflict with each other.
     * Space 1 is booked 09:00–12:00 but space 2 is free —
     * booking space 2 for the same window should succeed.
     */
    public function test_same_window_on_different_space_does_not_conflict(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 2, // different space from the seeded booking
            'user_id'          => 1,
            'start_time'       => '2026-03-01 09:00:00',
            'end_time'         => '2026-03-01 12:00:00',
        ]);

        $response->assertStatus(201);
    }

    // ── Booking validation ───────────────────────────────────────

    /**
     * parking_space_id must exist in the database.
     */
    public function test_returns_422_for_nonexistent_parking_space(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 999,
            'user_id'          => 1,
            'start_time'       => '2026-06-01 09:00:00',
            'end_time'         => '2026-06-01 12:00:00',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['parking_space_id']);
    }

    /**
     * end_time must be after start_time.
     */
    public function test_returns_422_when_booking_end_time_is_before_start_time(): void
    {
        $response = $this->postJson('/api/v1/bookings', [
            'parking_space_id' => 2,
            'user_id'          => 1,
            'start_time'       => '2026-06-01 12:00:00',
            'end_time'         => '2026-06-01 09:00:00',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['end_time']);
    }

    /**
     * All required fields must be present.
     */
    public function test_returns_422_when_required_booking_fields_are_missing(): void
    {
        $response = $this->postJson('/api/v1/bookings', []);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors([
                     'parking_space_id',
                     'user_id',
                     'start_time',
                     'end_time',
                 ]);
    }
}