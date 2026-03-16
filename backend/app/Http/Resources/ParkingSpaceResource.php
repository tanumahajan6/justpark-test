<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ParkingSpaceResource extends JsonResource
{
    /**
     * Controls exactly what the client receives.
     * Keeps internal model field names decoupled from the API contract.
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'location'     => $this->location,
            'hourly_price' => (float) $this->hourly_price,
        ];
    }
}