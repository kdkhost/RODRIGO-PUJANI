<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormSecurityLog extends Model
{
    protected $fillable = [
        'user_id',
        'portal_client_id',
        'route_name',
        'method',
        'path',
        'ip_address',
        'forwarded_for',
        'user_agent',
        'referer',
        'origin',
        'host',
        'session_id',
        'device_fingerprint',
        'reverse_dns',
        'country',
        'region',
        'city',
        'latitude',
        'longitude',
        'timezone',
        'isp',
        'organization',
        'asn',
        'payload_preview',
        'payload_field_count',
        'blocked',
        'block_reason',
        'threats',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_preview' => 'array',
            'threats' => 'array',
            'blocked' => 'boolean',
            'submitted_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }
}

