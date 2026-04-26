<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable([
    'seoable_type',
    'seoable_id',
    'route_name',
    'title',
    'description',
    'keywords',
    'hashtags',
    'og_title',
    'og_description',
    'og_image_path',
    'canonical_url',
    'robots',
    'schema_type',
    'noindex',
])]
class SeoMeta extends Model
{
    protected function casts(): array
    {
        return [
            'hashtags' => 'array',
            'noindex' => 'boolean',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }
}
