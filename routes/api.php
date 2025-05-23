<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TodosApiController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('todos', [TodosApiController::class, 'index'])->middleware('auth:sanctum');
Route::post('todos', [TodosApiController::class, 'store'])->middleware('auth:sanctum');
Route::put('todos', [TodosApiController::class, 'update'])->middleware('auth:sanctum');
