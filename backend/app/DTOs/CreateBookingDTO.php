<?php

namespace App\DTOs;

final class CreateBookingDTO
{
    public function __construct(
        public readonly int    $parkingSpaceId,
        public readonly int    $userId,
        public readonly string $startTime,
        public readonly string $endTime,
    ) {}

    public static function fromRequest(array $validated): self
    {
        return new self(
            parkingSpaceId: $validated['parking_space_id'],
            userId:         $validated['user_id'],
            startTime:      $validated['start_time'],
            endTime:        $validated['end_time'],
        );
    }
}