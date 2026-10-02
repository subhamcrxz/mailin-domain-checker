<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CheckBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'filename',
        'total',
        'queued',
        'checking',
        'completed',
        'failed',
        'status',
    ];

    protected $casts = [
        'total' => 'integer',
        'queued' => 'integer',
        'checking' => 'integer',
        'completed' => 'integer',
        'failed' => 'integer',
    ];

    public function checks(): HasMany
    {
        return $this->hasMany(DomainCheck::class, 'batch_id');
    }
}