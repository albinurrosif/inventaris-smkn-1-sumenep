<?php

use Illuminate\Support\Facades\Route;

Route::middleware('api')->group(function () {
    Route::get('/hello', function () {
        return response()->json(['message' => 'Hello from API!']);
    });
});

