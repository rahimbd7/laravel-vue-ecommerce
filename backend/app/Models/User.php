<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'email_verified_at',
        'uuid',
        'last_login_at'
    ];

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
    }


    public function profile() {
        return $this->hasOne(Profile::class, 'user_uuid', 'uuid');
    }
    public function vendor() {
        return $this->hasOne(Vendor::class, 'user_uuid', 'uuid');
    }

    public function orders() {
        return $this->hasMany(Order::class, 'user_id', 'id');
    }
     public function reviews() {
        return $this->hasMany(ProductReview::class, 'user_id', 'id');}
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

    //wishlist relationship
    public function wishlist(): HasMany {
        return $this->hasMany(Wishlist::class, 'user_uuid', 'uuid');
    }

    public function wishlistProducts(): HasManyThrough {
        return $this->hasManyThrough(
            Product::class,
            Wishlist::class,
            'user_uuid',
            'id',
            'uuid',
            'product_id'
        );
    }

    public function getWishlistCountAttribute(): int {
        return $this->wishlist()->count();
    }

    public function isInWishlist(int $productId): bool {
        return $this->wishlist()->where('product_id', $productId)->exists();
    }
}
