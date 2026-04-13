<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/requests/{id}', function ($id) {
    return response()->json(['id' => $id]);
})->name('document-requests.show');

Route::get('/upload/{token}', function ($token) {
    return response()->json(['token' => $token]);
})->name('guest.dossier.upload');
