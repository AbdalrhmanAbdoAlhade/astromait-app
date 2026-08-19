<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certifiable_type',
        'certifiable_id',
        'issued_by_id',
        'certificate_number',
        'qr_code_path',
        'meteorite_type',
        'origin_location',
        'discovery_date',
        'analysis_report_path',
        'issued_at',
    ];

    protected function casts(): array
    {
        return [
            'discovery_date' => 'date',
            'issued_at' => 'datetime',
        ];
    }

    public function certifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by_id');
    }

    public function verifications(): HasMany
    {
        return $this->hasMany(CertificateVerification::class);
    }
}
