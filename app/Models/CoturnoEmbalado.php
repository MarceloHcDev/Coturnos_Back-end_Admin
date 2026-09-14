<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoturnoEmbalado extends Model
{
    use HasFactory;

    protected $table = 'coturnos_embalados';

    protected $fillable = [
        'peso',
        'altura',
        'largura',
        'comprimento',
    ];

    protected $attributes = [
        'peso' => 1.50,
        'altura' => 14.00,
        'largura' => 22.00,
        'comprimento' => 35.00,
    ];

    protected $casts = [
        'peso' => 'decimal:2',
        'altura' => 'decimal:2',
        'largura' => 'decimal:2',
        'comprimento' => 'decimal:2',
    ];
}