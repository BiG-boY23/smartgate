<?php

namespace App\Models;

use App\Models\Visitor;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VehicleLog extends Model
{
    protected $fillable = [
        'vehicle_registration_id',
        'vehicle_id',
        'rfid_tag_id',
        'type',
        'timestamp'
    ];

    protected $casts = [
        'timestamp' => 'datetime'
    ];

    public function vehicleRegistration(): BelongsTo
    {
        return $this->belongsTo(VehicleRegistration::class, 'vehicle_registration_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    /**
     * Shared logic: calculates occupancy based on DAILY reset (Midnight to Midnight).
     * Occupancy = (Entries Today) - (Exits Today)
     */
    public static function dailyOccupancy()
    {
        $today = \Carbon\Carbon::today();
        
        $entries = static::where('type', 'entry')->whereDate('timestamp', $today)->count() 
                 + Visitor::whereDate('time_in', $today)->count();
                 
        $exits = static::where('type', 'exit')->whereDate('timestamp', $today)->count() 
               + Visitor::where('status', 'left')->whereDate('time_out', $today)->count();
        
        return max(0, $entries - $exits);
    }
}
