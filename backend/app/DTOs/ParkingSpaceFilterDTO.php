<?php

namespace App\DTOs;

/**
 * Carries validated filter criteria from the controller into the service.
 * Using a DTO instead of passing the raw Request object keeps the service
 * completely decoupled from the HTTP layer — it could be called from a
 * CLI command or a queue job just as easily.
 */
final class ParkingSpaceFilterDTO
{
    public function __construct(
        public readonly string  $startTime,
        public readonly string  $endTime,
        public readonly ?string $location = null,
        public readonly ?float  $maxPrice = null,
    ) {}

    // Factory: build from a validated Form Request
    public static function fromRequest(array $validated): self
    {
        return new self(
            startTime: $validated['start_time'],
            endTime:   $validated['end_time'],
            location:  $validated['location']  ?? null,
            maxPrice:  isset($validated['max_price'])
                           ? (float) $validated['max_price']
                           : null,
        );
    }
}