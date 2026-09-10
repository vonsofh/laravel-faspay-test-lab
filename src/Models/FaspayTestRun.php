<?php

namespace Vonso\FaspayTestLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class FaspayTestRun extends Model
{
    protected $fillable = [
        'public_id',
        'faspay_merchant_id',
        'service',
        'results',
        'status',
    ];

    public function getTable(): string
    {
        return config('faspay-test-lab.tables.runs', 'faspay_test_lab_runs');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    protected static function booted(): void
    {
        static::creating(function (self $run): void {
            $run->public_id ??= (string) Str::uuid();
        });
    }

    protected function casts(): array
    {
        return [
            'results' => 'array',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(FaspayMerchant::class, 'faspay_merchant_id');
    }
}
