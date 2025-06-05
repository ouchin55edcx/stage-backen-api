<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class License extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'key',
        'expiration_date',
        'equipment_id'
    ];

    protected $casts = [
        'expiration_date' => 'date'
    ];

    /**
     * Get the equipment that owns the license.
     */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
