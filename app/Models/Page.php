<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable([
    'title',
    'slug',
    'menu_title',
    'template',
    'theme_variant',
    'status',
    'is_home',
    'show_in_menu',
    'sort_order',
    'hero_title',
    'hero_subtitle',
    'hero_cta_label',
    'hero_cta_url',
    'cover_path',
    'excerpt',
    'body',
    'content',
    'published_at',
])]
class Page extends Model
{
    protected function casts(): array
    {
        return [
            'content' => 'array',
            'is_home' => 'boolean',
            'show_in_menu' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order');
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
