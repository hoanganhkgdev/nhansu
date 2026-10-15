<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'part_id', 'seq', 'fullname', 'dharma_name',
    'birthday', 'image', 'email', 'phone',
    'member_code', 'id_number', 'id_issued_date', 'id_issued_place', 'position',
    'resident', 'precept_rank', 'clergy_rank', 'education',
    'sort', 'is_priority', 'is_active',
])]
class Official extends Model
{
    /** Text columns are NOT NULL (default ''), but form inputs submit null when left empty. */
    private const TEXT_COLUMNS = [
        'fullname', 'dharma_name', 'image', 'email', 'phone', 'member_code', 'id_number',
        'id_issued_place', 'position', 'resident', 'precept_rank', 'clergy_rank', 'education',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $official) {
            foreach (self::TEXT_COLUMNS as $column) {
                if (array_key_exists($column, $official->getAttributes()) && $official->getAttribute($column) === null) {
                    $official->setAttribute($column, '');
                }
            }
        });

        static::creating(function (self $official) {
            if (empty($official->seq)) {
                $official->seq = (int) static::max('seq') + 1;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'birthday' => 'date',
            'id_issued_date' => 'date',
            'is_priority' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopePublished(Builder $query): void
    {
        $query->where('is_active', true);
    }

    /** Same ordering the old site used: priority first, then insertion order. */
    public function scopeListOrder(Builder $query): void
    {
        $query->orderByDesc('is_priority')->orderBy('sort')->orderBy('id');
    }

    /**
     * Free-text search over the same fields the old search covered.
     * Bound parameters make this safe, unlike the old string-concatenated SQL.
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || trim($term) === '') {
            return;
        }

        $like = '%'.addcslashes(trim($term), '%_\\').'%';
        $query->where(function (Builder $q) use ($like) {
            foreach (['fullname', 'dharma_name', 'resident', 'email', 'phone', 'member_code', 'precept_rank', 'clergy_rank', 'education'] as $column) {
                $q->orWhere($column, 'like', $like);
            }
        });
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->fullname) ?: 'nhan-su';
    }

    public function getImageUrlAttribute(): ?string
    {
        if ($this->image === '') {
            return null;
        }

        return Storage::disk('officials')->url($this->image);
    }
}
