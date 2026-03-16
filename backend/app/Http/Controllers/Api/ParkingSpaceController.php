<?php

namespace App\Http\Controllers\Api;

use App\DTOs\ParkingSpaceFilterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListParkingSpacesRequest;
use App\Http\Resources\ParkingSpaceResource;
use App\Services\ParkingSpaceService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ParkingSpaceController extends Controller
{
    public function __construct(
        // Laravel injects the service automatically via the container
        private readonly ParkingSpaceService $parkingSpaceService
    ) {}

    public function index(ListParkingSpacesRequest $request): AnonymousResourceCollection
    {
        // Request validates input → DTO carries it → Service runs the query
        $dto    = ParkingSpaceFilterDTO::fromRequest($request->validated());
        $spaces = $this->parkingSpaceService->getAvailableSpaces($dto);

        return ParkingSpaceResource::collection($spaces);
    }
}