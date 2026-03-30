<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\VehicleBrand;

class VehicleCategory extends Model
{
    protected $fillable = ['name', 'icon', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function brands(): BelongsToMany
    {
        return $this->belongsToMany(VehicleBrand::class, 'vehicle_brand_category', 'vehicle_category_id', 'vehicle_brand_id');
    }
}
