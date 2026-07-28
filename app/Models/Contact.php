<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'first_name',
        'last_name',
        'gender',
        'email',
        'tel',
        'address',
        'building',
        'detail',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)
            ->withTimestamps();
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        $keyword = $filters['keyword'] ?? null;
        $gender = (int) ($filters['gender'] ?? 0);
        $categoryId = $filters['category_id'] ?? null;
        $date = $filters['date'] ?? null;

        if ($keyword) {
            $query->where(function (Builder $query) use ($keyword) {
                $query
                    ->where('first_name', 'like', "%{$keyword}%")
                    ->orWhere('last_name', 'like', "%{$keyword}%")
                    ->orWhere('email', $keyword);
            });
        }

        if ($gender !== 0) {
            $query->where('gender', $gender);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        if ($date) {
            $query->whereDate('created_at', $date);
        }

        return $query;
    }
}
