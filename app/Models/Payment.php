<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $table = 'payments';

    protected $fillable = [
        'gateway',
        'preference_id',
        'init_point',
        'gateway_payment_id',
        'method',
        'status',
        'amount',
        'installments',
        'qr_code_pix',
        'qr_code_base64',
        'response_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount'           => 'decimal:2',
            'installments'     => 'integer',
            'response_payload' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}