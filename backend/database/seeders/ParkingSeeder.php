<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\ParkingSpace;
use Illuminate\Database\Seeder;

class ParkingSeeder extends Seeder
{
    public function run(): void
    {
        // Seed fixture data exactly as specified in the brief
        $spaces = [
            ['id' => 1, 'location' => 'City Center Garage A', 'hourly_price' => 5.00],
            ['id' => 2, 'location' => 'City Center Garage A', 'hourly_price' => 5.00],
            ['id' => 3, 'location' => 'Suburban Lot B',       'hourly_price' => 3.50],
            ['id' => 4, 'location' => 'Airport Parking C',    'hourly_price' => 7.50],
        ];

        foreach ($spaces as $space) {
            ParkingSpace::create($space);
        }

        $bookings = [
            [
                'id' => 101, 'parking_space_id' => 1, 'user_id' => 1,
                'start_time' => '2026-03-01 09:00:00',
                'end_time'   => '2026-03-01 12:00:00',
            ],
            [
                'id' => 102, 'parking_space_id' => 3, 'user_id' => 2,
                'start_time' => '2026-03-02 14:30:00',
                'end_time'   => '2026-03-02 18:00:00',
            ],
        ];

        foreach ($bookings as $booking) {
            Booking::create($booking);
        }
    }
}