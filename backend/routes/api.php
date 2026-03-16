<?php
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ParkingSpaceController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/parking-spaces', [ParkingSpaceController::class, 'index']);
    Route::post('/bookings',      [BookingController::class, 'store']);
});