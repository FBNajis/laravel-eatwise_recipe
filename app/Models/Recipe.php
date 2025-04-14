<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Recipe extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'cost_estimation',
        'cooking_time',
        'ingredients',
        'instructions',
        'tag',
        'image_path',
        'status',
    ];

    protected $appends = [
        'favorites_count',
        'image_url'
    ];

    // Relasi ke pembuat resep
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'fullname' => 'Unknown User'
        ]);
    }

    // Relasi ke user yang menyukai resep ini
    public function favorites()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }


    // Accessor untuk jumlah favorit
    public function getFavoritesCountAttribute()
    {
        return array_key_exists('favorites_count', $this->attributes)
            ? $this->attributes['favorites_count']
            : $this->favorites()->count();
    }

    // Accessor untuk URL gambar lengkap
    public function getImageUrlAttribute()
    {
        return $this->image_path
            ? Storage::disk('public')->url($this->image_path)
            : null;
    }

    // Scope untuk hanya resep yang sudah di-approve admin
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    // Handler untuk delete dan update gambar otomatis
    protected static function booted()
    {
        static::deleting(function ($recipe) {
            if ($recipe->image_path) {
                Storage::disk('public')->delete($recipe->image_path);
            }
        });

        static::updating(function ($recipe) {
            $original = $recipe->getOriginal();
            if ($recipe->isDirty('image_path') && $original['image_path']) {
                Storage::disk('public')->delete($original['image_path']);
            }
        });
    }
}
