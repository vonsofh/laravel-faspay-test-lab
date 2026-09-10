<?php

namespace Vonso\FaspayTestLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaspayTestRun extends Model
{
    protected $fillable = [
        'faspay_merchant_id',
        'service',
        'results',
        'status',
    ];

    public function getTable(): string
    {
        return config('faspay-test-lab.tables.runs', 'faspay_test_lab_runs');
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
