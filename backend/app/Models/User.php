<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable , SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     *
     */
    protected static function booted() {
        static::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
        });
        static::created(function ($user) {
            $user->profile()->create();
        });
        static::deleted(function ($user) {
            if ($user->profile) {
                $user->profile->delete();
            }
        });
        static::restored(function ($user) {
             if ($user->profile()->withTrashed()->exists()) {
                $user->profile()->withTrashed()->restore();
            }
        });
    }

    public function profile() {
        return $this->hasOne(Profile::class, 'user_uuid', 'uuid');
    }
    public function vendor() {
        return $this->hasOne(Vendor::class, 'user_uuid', 'uuid');
    }

    //Role check methods
    public function isAdmin(): bool {
        return $this->role === 'admin';
    }
    public function isVendor(): bool {
        return $this->role === 'vendor';
    }
    public function isCustomer(): bool {
        return $this->role === 'customer';
    }
    public function isVerifiedVendor(): bool {
        return $this->isVendor() && $this->vendor && $this->vendor?->is_verified;
    }

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
