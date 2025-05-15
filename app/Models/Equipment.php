<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasFactory;

    public function interventions()
    {
        return $this->hasMany(Intervention::class);
    }

    protected $table = 'equipments';

    protected $fillable = [
        'name',
        'type',
        'nsc',
        'status',
        'ip_address',
        'serial_number',
        'processor',
        'brand',
        'office_version',
        'label',
        'backup_enabled',
        'employer_id',
    ];

    protected $casts = [
        'backup_enabled' => 'boolean',
    ];

    public function employer()
    {
        return $this->belongsTo(Employer::class);
    }
}
