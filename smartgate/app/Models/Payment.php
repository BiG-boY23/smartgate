<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'vehicle_registration_id',
        'or_number',
        'amount',
        'paid_at'
    ];

    protected $casts = [
        'paid_at' => 'datetime'
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(VehicleRegistration::class, 'vehicle_registration_id');
    }
}
