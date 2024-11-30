<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'masked_card_number',
        'card_hash',
        'amount',
        'currency',
        'customer_email',
        'status',
        'metadata'
    ];

    protected $hidden = ['card_hash'];

    protected $casts = [
        'metadata' => 'array',
        'amount' => 'decimal:2'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->id = (string) Str::uuid();
        });
    }
}
