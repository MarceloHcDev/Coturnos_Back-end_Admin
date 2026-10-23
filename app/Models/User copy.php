<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany; // Importação recomendada
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable; // mantenha os traits que já existem


    protected $fillable = [
        'first_name',
        'last_name',
        'cpf',
        'email',
        'phone',
        'birth_date',
        'password',
        'role',
        'active',
    ];

    /**
     * Os atributos que devem ser ocultados na serialização (ex: respostas de API / JSON).
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Mapeamento de conversão de tipos nativos e criptografia (Casting).
     */
    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'password' => 'hashed',
            'active' => 'boolean',
            'cpf' => 'encrypted',   // Criptografia automática de/para o banco (AES-256 via APP_KEY)
            'phone' => 'encrypted', // Criptografia automática de/para o banco (AES-256 via APP_KEY)
            'role' => UserRole::class,
        ];

       
    }

    /**
     * CORRIGIDO: Sintaxe de interpolação de strings em PHP.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->role === UserRole::CUSTOMER;
    }

    
    /**
     * MELHORADO: Adicionado tipo de retorno nativo do Laravel.
     */
    public function address(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function order(): HasMany
    {
        return $this->hasMany(Order::class);
    }

 
}