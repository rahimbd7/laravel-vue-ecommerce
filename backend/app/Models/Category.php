<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model {
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;
    protected $fillable = [
        'uuid',
        'name',
        'slug',
        'description',
        'image',
        'icon',
        'parent_id',
        'position',
        'is_active',
        'is_featured',
        'product_count',
        'meta_title',
        'meta_description',
        'meta_keywords',
    ];
    protected $casts = [
        'is_active'     => 'boolean',
        'is_featured'   => 'boolean',
        'meta_keywords' => 'array',
    ];

    public function parent() {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children() {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products() {
        return $this->hasMany(Product::class);
    }
    public function vendors() {
        return $this->hasManyThrough(Vendor::class, Product::class);
    }
    //accessors
    public function getImageUrlAttribute() {
        return $this->image ? asset('storage/' . $this->image) : null;
    }
    //scopes
    public function scopeActive($query) {
        return $query->where('is_active', true);
    }
    public function scopeRoot($query) {
        return $query->where('parent_id', null);
    }
}
