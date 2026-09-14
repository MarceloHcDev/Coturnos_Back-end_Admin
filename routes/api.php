<?php

use App\Http\Controllers\CepController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\FreteController;
use Illuminate\Support\Facades\Route;

Route::get('/consulta-cep/{cep}', [CepController::class, 'consultar']);
Route::post('/upload-store-images', [ImageController::class, 'upload']);
Route::post('/frete', [FreteController::class, 'calcular']);