<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Profile extends Model
{
    protected $table = 'profiles';

    protected $fillable = [
        'user_uuid',
        'phone',
        'avatar',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'date_of_birth'
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

    // Check if profile has complete address
    public function hasCompleteAddress(): bool
    {
        return !empty($this->address) && !empty($this->city) && !empty($this->country);
    }

    // Get full address as string
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->state,
            $this->postal_code,
            $this->country,
        ]);
        return implode(', ', $parts);
    }
}
