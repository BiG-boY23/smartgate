<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value'];

    /**
     * Get a setting by key.
     */
    public static function get($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting by key.
     */
    public static function set($key, $value)
    {
        try {
            self::where('key', $key)->delete();
            return self::create(['key' => $key, 'value' => $value]);
        } catch (\Exception $e) {
            return false;
        }
    }
}
