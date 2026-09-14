<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SuperFreteService;

class ShippingController extends Controller
{
    protected SuperFreteService $superFrete;

    public function __construct(SuperFreteService $superFrete)
    {
        $this->superFrete = $superFrete;
    }

    public function calculate(Request $request)
    {
        $validated = $request->validate([
            'to_postal_code' => 'required|string|size:8',
            'weight' => 'required|numeric',
            'height' => 'required|numeric',
            'width' => 'required|numeric',
            'length' => 'required|numeric',
        ]);

        try {
            // CEP de origem fixo da loja ou obtido dinamicamente
            $validated['from_postal_code'] = '12345678'; 

            $result = $this->superFrete->calculate($validated);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}