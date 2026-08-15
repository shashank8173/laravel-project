<?php

use App\Http\Controllers\Api\ConnectorWebhookController;
use App\Http\Controllers\Api\EmployeeApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', function () {
        return response()->json([
            'status' => 'success',
            'message' => 'Leadforgrow HRM API is online.',
            'time' => now()->toIso8601String(),
        ]);
    });

    Route::post('/connectors/{slug}/webhook', [ConnectorWebhookController::class, 'webhook'])
        ->where('slug', '[A-Za-z0-9\\-]+');

    Route::middleware('api.token:employees:read')->group(function () {
        Route::get('/employees', [EmployeeApiController::class, 'index']);
        Route::get('/employees/{employee}', [EmployeeApiController::class, 'show']);
    });

    Route::middleware('api.token:employees:write')->group(function () {
        Route::post('/employees', [EmployeeApiController::class, 'store']);
        Route::post('/employees/upsert', [EmployeeApiController::class, 'upsert']);
        Route::put('/employees/{employee}', [EmployeeApiController::class, 'update']);
        Route::patch('/employees/{employee}', [EmployeeApiController::class, 'update']);
        Route::delete('/employees/{employee}', [EmployeeApiController::class, 'destroy']);
    });
});
