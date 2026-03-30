<?php

use App\Models\VehicleRegistration;

foreach (VehicleRegistration::all() as $v) {
    if (!$v->first_name && $v->full_name && $v->full_name !== 'N/A') {
        $parts = explode(' ', trim($v->full_name));
        if (count($parts) >= 3) {
            $v->first_name = $parts[0];
            $v->last_name = $parts[count($parts) - 1];
            $v->middle_name = $parts[1]; // simplified assume first middle name
        } elseif (count($parts) == 2) {
            $v->first_name = $parts[0];
            $v->last_name = $parts[1];
        } else {
            $v->first_name = $v->full_name;
        }
        $v->save();
    }
}

echo "Migration completed.\n";
