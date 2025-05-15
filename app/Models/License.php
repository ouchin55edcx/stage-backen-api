<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'key',
        'expiration_date'
    ];

    protected $casts = [
        'expiration_date' => 'date'
    ];
}
