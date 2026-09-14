<?php

use Illuminate\Support\Facades\Route;
use Cloudinary\Configuration\Configuration;
use Cloudinary\Api\Upload\UploadApi;
use App\Http\Controllers\CepController;

Route::get('/', function () {
    return view('welcome');
});

// Rota para consultar o CEP
Route::get('/consulta-cep/{cep}', [CepController::class, 'consultar']);

Route::get('/teste-cloudinary', function () {
    try {
        // Inicializa a configuração do Cloudinary
        Configuration::instance([
            'cloud' => [
                'cloud_name' => env('CLOUDINARY_CLOUD_NAME'),
                'api_key'    => env('CLOUDINARY_API_KEY'),
                'api_secret' => env('CLOUDINARY_API_SECRET'),
            ],
            'url' => [
                'secure' => true
            ]
        ]);

        // Cria uma imagem simulada em base64 (um pixel transparente/branco) para testar o upload
        $base64Image = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

        // Realiza o upload direto da string para o Cloudinary
        $upload = (new UploadApi())->upload($base64Image);
        
        $urlSegura = $upload['secure_url'];

        return "Upload realizado com sucesso! URL da imagem no Cloudinary: <a href='{$urlSegura}' target='_blank'>{$urlSegura}</a>";
    } catch (\Exception $e) {
        return "Erro ao conectar com o Cloudinary: " . $e->getMessage();
    }
});

