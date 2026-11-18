<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'products';

    protected $fillable = [
        'name',
        'slug',
        'product_type',
        'brand',
        'collection',        // ex.: 'classico', 'elite' (filtro da home)
        'badge',             // ex.: 'Novo', 'Oferta' (selo no card)
        'description',
        'image_public_id',   // public_id da imagem principal no Cloudinary
        'hover_public_id',   // public_id da imagem de hover no Cloudinary
        'base_price',
        'promotional_price',
        'weight',
        'height',
        'width',
        'length',
        'active',
    ];

    protected $casts = [
        'base_price'        => 'decimal:2',
        'promotional_price' => 'decimal:2',
        'weight'            => 'decimal:2',
        'height'            => 'decimal:2',
        'width'             => 'decimal:2',
        'length'            => 'decimal:2',
        'active'            => 'boolean',
    ];

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}