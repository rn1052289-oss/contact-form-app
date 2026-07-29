<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
    ];

    /**
     * このカテゴリに紐づくお問い合わせを取得する
     */
    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }
}
