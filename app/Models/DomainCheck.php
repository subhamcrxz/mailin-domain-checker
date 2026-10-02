<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'input',
        'domain',
        'dkim_selector',
        'status',
        'blacklist_status',
        'blacklists',
        'dns_records',
        'provider',
        'detection_evidence',
        'error',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'blacklists' => 'array',
        'dns_records' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(CheckBatch::class, 'batch_id');
    }
}