<?php

namespace App\Http\Controllers\Api;

use App\DTOs\CreateBookingDTO;
use App\Exceptions\BookingConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateBookingRequest;
use App\Http\Resources\BookingResource;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService
    ) {}

    public function store(CreateBookingRequest $request): BookingResource|JsonResponse
    {
        try {
            $dto     = CreateBookingDTO::fromRequest($request->validated());
            $booking = $this->bookingService->createBooking($dto);

            return (new BookingResource($booking))
                ->response()
                ->setStatusCode(201);

        } catch (BookingConflictException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error'   => 'conflict',
            ], 409);
        }
    }
}