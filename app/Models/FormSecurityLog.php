<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class FormSecurityLog extends Model
{
    protected $fillable = [
        'user_id',
        'portal_client_id',
        'security_access_block_id',
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
        'device_id',
        'device_type',
        'device_platform',
        'device_model',
        'browser_name',
        'browser_version',
        'os_name',
        'os_version',
        'network_type',
        'mac_address',
        'device_metadata',
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
            'device_metadata' => 'array',
            'threats' => 'array',
            'blocked' => 'boolean',
            'submitted_at' => 'datetime',
            'latitude' => 'float',
            'longitude' => 'float',
        ];
    }

    public function block(): BelongsTo
    {
        return $this->belongsTo(SecurityAccessBlock::class, 'security_access_block_id');
    }
}
