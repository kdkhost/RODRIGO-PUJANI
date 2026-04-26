<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

#[Fillable([
    'name',
    'slug',
    'role',
    'oab_number',
    'email',
    'phone',
    'whatsapp',
    'bio',
    'specialties',
    'image_path',
    'linkedin_url',
    'instagram_url',
    'is_partner',
    'is_active',
    'sort_order',
])]
class TeamMember extends Model
{
    protected function casts(): array
    {
        return [
            'specialties' => 'array',
            'is_partner' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function seoMeta(): MorphOne
    {
        return $this->morphOne(SeoMeta::class, 'seoable');
    }
}
