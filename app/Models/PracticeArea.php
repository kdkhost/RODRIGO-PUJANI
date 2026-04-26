<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable([
    'title',
    'slug',
    'icon',
    'highlight',
    'short_description',
    'description',
    'image_path',
    'is_featured',
    'is_active',
    'sort_order',
])]
class PracticeArea extends Model
{
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
