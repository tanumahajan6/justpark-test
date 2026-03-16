<?php

namespace App\Services;

use App\DTOs\ParkingSpaceFilterDTO;
use App\Models\ParkingSpace;
use Illuminate\Database\Eloquent\Collection;

class ParkingSpaceService
{
    /**
     * Return all parking spaces that are NOT booked during the requested window.
     *
     * Overlap condition (the key logic):
     *   An existing booking overlaps a requested window if:
     *     existing.start_time < requested.end_time
     *     AND existing.end_time > requested.start_time
     *
     *   This single condition catches ALL overlap cases:
     *     - Full overlap (existing wraps around requested)
     *     - Partial overlap from the left
     *     - Partial overlap from the right
     *     - Identical windows
     */
    public function getAvailableSpaces(ParkingSpaceFilterDTO $dto): Collection
    {
        return ParkingSpace::query()
            ->when(
                $dto->location,
                fn($q) => $q->where('location', 'like', "%{$dto->location}%")
            )
            ->when(
                $dto->maxPrice,
                fn($q) => $q->where('hourly_price', '<=', $dto->maxPrice)
            )
            ->whereDoesntHave('bookings', fn($q) =>
                $q->where('start_time', '<', $dto->endTime)
                  ->where('end_time',   '>', $dto->startTime)
            )
            ->get();
    }
}