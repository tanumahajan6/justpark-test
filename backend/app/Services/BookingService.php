<?php

namespace App\Services;

use App\DTOs\CreateBookingDTO;
use App\Exceptions\BookingConflictException;
use App\Models\Booking;

class BookingService
{
    /**
     * Create a booking after checking for conflicts.
     * Throws BookingConflictException if the space is already taken.
     */
    public function createBooking(CreateBookingDTO $dto): Booking
    {
        // Re-use the same overlap logic as the availability check
        $conflict = Booking::where('parking_space_id', $dto->parkingSpaceId)
            ->where('start_time', '<', $dto->endTime)
            ->where('end_time',   '>', $dto->startTime)
            ->lockForUpdate()
            ->exists();

        if ($conflict) {
            throw new BookingConflictException(
                'This parking space is already booked for the requested time range.'
            );
        }

        return Booking::create([
            'parking_space_id' => $dto->parkingSpaceId,
            'user_id'          => $dto->userId,
            'start_time'       => $dto->startTime,
            'end_time'         => $dto->endTime,
        ]);
    }
}