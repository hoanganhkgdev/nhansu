<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

#[Fillable(['title', 'slug', 'office', 'address', 'phone', 'fax', 'website', 'email', 'note', 'is_active'])]
class Part extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $part) {
            if (empty($part->slug)) {
                $base = Str::slug($part->title) ?: 'don-vi';
                $slug = $base;
                for ($n = 2; static::where('slug', $slug)->exists(); $n++) {
                    $slug = $base.'-'.$n;
                }
                $part->slug = $slug;
            }
        });
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function officials(): HasMany
    {
        return $this->hasMany(Official::class);
    }
}
