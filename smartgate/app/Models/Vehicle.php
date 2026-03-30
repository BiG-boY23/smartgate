<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    protected $fillable = [
        'user_id',
        'plate_number',
        'vehicle_details',
        'rfid_tag',
        'vehicle_type',
        'expiry_date'
    ];

    protected $casts = [
        'expiry_date' => 'date'
    ];

    /**
     * Get the owner (VehicleRegistration) of this vehicle.
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(VehicleRegistration::class, 'user_id');
    }

    /**
     * Get the logs for this specific vehicle.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(VehicleLog::class, 'vehicle_id');
    }
}
