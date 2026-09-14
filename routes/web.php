<?php

use Illuminate\Support\Facades\Route;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use App\Http\Controllers\CepController;

Route::get('/', function () {
    return view('welcome');
});



