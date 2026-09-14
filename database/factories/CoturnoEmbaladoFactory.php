<?php

namespace Database\Factories;

use App\Models\CoturnoEmbalado;
use Illuminate\Database\Eloquent\Factories\Factory;

class CoturnoEmbaladoFactory extends Factory
{
    protected $model = CoturnoEmbalado::class;

    public function definition(): array
    {
        return [
            'peso' => 1.50,
            'altura' => 14.00,
            'largura' => 22.00,
            'comprimento' => 35.00,
        ];
    }
}