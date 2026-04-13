<?php

use App\Http\Controllers\Api\V1\ComplianceController;
use App\Http\Controllers\Api\V1\DocumentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->prefix('v1')->group(function () {
    // Rotta per il controllo di conformità massivo
    Route::post('/compliance/check', [ComplianceController::class, 'checkGate']);

    // Rotta (opzionale) per leggere lo storico di un singolo documento
    Route::get('/documents/{id}', [DocumentController::class, 'show']);
});
