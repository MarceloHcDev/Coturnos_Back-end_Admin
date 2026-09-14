<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class FreteController extends Controller
{
   public function calcular(Request $request)
{
    $validated = $request->validate([
        'cep_destino' => 'required|string',
        'cep_origem'  => 'nullable|string',
        'peso'        => 'required|numeric',
        'altura'      => 'required|numeric',
        'largura'     => 'required|numeric',
        'comprimento' => 'required|numeric',
    ]);

    $response = Http::withToken(config('services.super_frete.token'))
        ->withHeaders([
            'User-Agent' => 'MinhaApp v1.0 (marcelodevcore@gmail.com)',
            'Accept'     => 'application/json',
        ])
        ->post(config('services.super_frete.url'), [
            'from' => ['postal_code' => $validated['cep_origem'] ?? '12510020'],
            'to'   => ['postal_code' => $validated['cep_destino']],
            'services' => '1,2,17',
            'options' => [
                'own_hand' => false,
                'receipt' => false,
                'insurance_value' => 0,
                'use_insurance_value' => false,
            ],
            'package' => [
                'weight' => $validated['peso'],
                'height' => $validated['altura'],
                'width'  => $validated['largura'],
                'length' => $validated['comprimento'],
            ],
        ]);

    if ($response->failed()) {
        return response()->json([
            'error' => 'Erro ao consultar SuperFrete',
            'status' => $response->status(),
            'body' => $response->json(),
        ], $response->status());
    }

    return response()->json($response->json());
    }
}