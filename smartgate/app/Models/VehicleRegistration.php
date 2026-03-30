<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VehicleRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'role',
        'first_name',
        'last_name',
        'middle_name',
        'full_name',
        'university_id',
        'college_dept',
        'contact_number',
        'email_address',
        'course',
        'year_level',
        'rank',
        'office',
        'business_stall_name',
        'vendor_address',
        'vehicle_type',
        'registered_owner',
        'make_brand',
        'model_name',
        'model_year',
        'color',
        'plate_number',
        'engine_number',
        'sticker_classification',
        'requirements',
        'validity_from',
        'validity_to',
        'rfid_tag_id',
        'status',
        'office_user_id',
        'cr_path',
        'or_path',
        'cor_path',
        'student_id_path',
        'license_path',
        'employee_id_path',
        'payment_receipt_path',
    ];

    protected $casts = [
        'sticker_classification' => 'array',
        'requirements' => 'array',
        'validity_from' => 'date',
        'validity_to' => 'date',
    ];

    /**
     * Get the office user who created this registration
     */
    public function officeUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'office_user_id');
    }

    /**
     * Get all admin reviews for this registration
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(RegistrationReview::class, 'vehicle_registration_id');
    }

    /**
     * Get the latest admin review (ordered by reviewed_at desc)
     */
    public function latestReview()
    {
        return $this->hasOne(RegistrationReview::class, 'vehicle_registration_id')
            ->latestOfMany('reviewed_at');
    }

    /**
     * Convenience accessor: admin who performed the latest review.
     * Use eager loading via latestReview.admin to avoid extra queries.
     */
    public function getAdminAttribute()
    {
        return $this->latestReview?->admin;
    }

    public function vehicles(): HasMany
    {
        return $this->hasMany(Vehicle::class, 'user_id');
    }

    /**
     * Get all payments made for this registration.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'vehicle_registration_id');
    }
}
