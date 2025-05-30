<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Maintenance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'intervention_id',
        'maintenance_type',
        'scheduled_date',
        'performed_date',
        'next_maintenance_date',
        'observations',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_date' => 'date',
        'performed_date' => 'date',
        'next_maintenance_date' => 'date',
    ];

    /**
     * Get the intervention that this maintenance is for.
     */
    public function intervention(): BelongsTo
    {
        return $this->belongsTo(Intervention::class);
    }
}
