<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Models\VehicleCategory;

class VehicleBrand extends Model
{
    protected $fillable = ['name'];

    public function models(): HasMany
    {
        return $this->hasMany(VehicleModel::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(VehicleCategory::class, 'vehicle_brand_category', 'vehicle_brand_id', 'vehicle_category_id');
    }
}
