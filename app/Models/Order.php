<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'order_number',
        'user_id',
        'address_id',
        'status',
        'subtotal',
        'shipping_fee',
        'discount_fee',
        'total_amount',
        'shipping_method',
        'tracking_code',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'shipping_fee' => 'decimal:2',
            'discount_fee' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateUniqueOrderNumber();
            }
        });
    }

    public static function generateUniqueOrderNumber(): string
    {
        do {
            $prefix = 'ORD';
            $date = now()->format('Ymd');
            $random = strtoupper(Str::random(6));

            $orderNumber = "{$prefix}-{$date}-{$random}";
        } while (static::where('order_number', $orderNumber)->exists());

        return $orderNumber;
    }

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->HasOne(Adress::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function orderItem()
    {
        return $this->hasMany(OrderItem::class);
    }
}