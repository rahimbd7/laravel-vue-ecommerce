<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    /** @use HasFactory<\Database\Factories\ProfileFactory> */
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'user_uuid',
        'phone',
        'avatar',
        'address',
        'date_of_birth',
    ];
    protected $casts = [
        'date_of_birth' => 'date',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }

}

