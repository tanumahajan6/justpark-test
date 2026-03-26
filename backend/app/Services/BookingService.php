<?php

namespace App\Services;

use App\DTOs\CreateBookingDTO;
use App\Exceptions\BookingConflictException;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class BookingService
{
    /**
     * Create a booking after checking for conflicts.
     * Throws BookingConflictException if the space is already taken.
     *
     * Wrapped in a DB::transaction so that lockForUpdate holds a
     * row-level lock for the duration of the conflict check + insert,
     * preventing double-bookings under concurrent requests.
     */
    public function createBooking(CreateBookingDTO $dto): Booking
    {
        return DB::transaction(function () use ($dto) {
            // Re-use the same overlap logic as the availability check
            $conflict = Booking::where('parking_space_id', $dto->parkingSpaceId)
                ->where('start_time', '<', $dto->endTime)
                ->where('end_time', '>', $dto->startTime)
                ->lockForUpdate()
                ->exists();

            if ($conflict) {
                throw new BookingConflictException(
                    'This parking space is already booked for the requested time range.'
                );
            }

            return Booking::create([
                'parking_space_id' => $dto->parkingSpaceId,
                'user_id' => $dto->userId,
                'start_time' => $dto->startTime,
                'end_time' => $dto->endTime,
            ]);
        });
    }
}