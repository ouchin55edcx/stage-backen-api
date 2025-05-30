<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Equipment;

class Intervention extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'technician_name',
        'note',
        'equipment_id'
    ];

    protected $casts = [
        'date' => 'datetime'
    ];

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    public function maintenances()
    {
        return $this->hasMany(Maintenance::class);
    }
}
