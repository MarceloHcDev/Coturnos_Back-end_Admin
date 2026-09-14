<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CepController extends Controller
{
    public function consultar($cep)
    {
        // Remove caracteres não numéricos do CEP
        $cepLimpo = preg_replace('/[^0-9]/', '', $cep);

        // Validação básica de tamanho
        if (strlen($cepLimpo) !== 8) {
            return response()->json(['erro' => 'CEP inválido.'], 400);
        }

        // Faz a requisição para a API do ViaCEP
        $response = Http::get("https://viacep.com.br/ws/{$cepLimpo}/json/");

        // Verifica se a requisição falhou ou se o CEP não existe
        if ($response->failed() || isset($response->json()['erro'])) {
            return response()->json(['erro' => 'CEP não encontrado.'], 404);
        }

        // Retorna os dados em formato JSON para o front-end
        return response()->json($response->json());
    }
}
