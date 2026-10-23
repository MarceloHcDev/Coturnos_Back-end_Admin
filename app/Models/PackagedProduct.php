<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackagedProduct extends Model
{
    use HasFactory;

    protected $table = 'PackagedProducts';

    protected $fillable = [
        'weight',
        'height',
        'width',
        'length',
    ];

    protected function casts(): array
    {
        return [
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'length' => 'decimal:2',
        ];
    }
}