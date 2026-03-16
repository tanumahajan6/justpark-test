<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'parking_space_id' => $this->parking_space_id,
            'user_id'          => $this->user_id,
            'start_time'       => $this->start_time->toDateTimeString(),
            'end_time'         => $this->end_time->toDateTimeString(),
        ];
    }
}