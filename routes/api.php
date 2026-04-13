<?php

use App\Http\Controllers\Api\V1\ComplianceController;
use App\Http\Controllers\Api\V1\DocumentController;
use App\Http\Controllers\Api\V1\DocumentRequestController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1')->group(function () {
    // Rotta per il controllo di conformità massivo
    Route::post('/compliance/check', [ComplianceController::class, 'checkGate']);

    // Rotta per la creazione di un Dossier (DocumentRequest)
    Route::post('/requests', [DocumentRequestController::class, 'store']);

    // Rotta (opzionale) per leggere lo storico di un singolo documento
    Route::get('/documents/{id}', [DocumentController::class, 'show']);
});
