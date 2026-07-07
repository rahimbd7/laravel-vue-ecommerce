<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model {
    /** @use HasFactory<\Database\Factories\VendorFactory> */
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'user_uuid',
        'business_name',
        'business_email',
        'business_phone',
        'tax_number',
        'website',
        'description',
        'is_verified',
        'verified_at',
        'status',
        'rejected_at',
        'rejection_reason',
        'commission_rate',
        'store_logo',
        'shipping_settings',
    ];

    protected $casts = [
        'is_verified'       => 'boolean',
        'commission_rate'   => 'decimal:2',
        'shipping_settings' => 'array',
    ];

    //relationships
    public function user() {
        return $this->belongsTo(User::class, 'user_uuid', 'uuid');
    }
    public function products() {
        return $this->hasMany(Product::class);
    }
    //scopes
    public function scopeVerified($query) {
        return $query->where('is_verified', true);
    }
    public function vendorPayouts() {
        return $this->hasMany(VendorPayout::class);
    } 
    public function vendorBalance() {
        return $this->hasOne(VendorBalance::class);
    }
    public function orders() {
        return $this->hasMany(Order::class);
    }
 
    public function scopePending($query) {
        return $query
            ->where('status', 'pending')
            ->where('is_verified', false)
            ->whereNull('rejected_at');
    }
    //helpers
    public function markAsVerified() {
        $this->update(['is_verified' => true]);
    }
    public function reject() {
        $this->delete();
    }
}
