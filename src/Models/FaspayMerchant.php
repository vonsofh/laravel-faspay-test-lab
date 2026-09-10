<?php

namespace Vonso\FaspayTestLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaspayMerchant extends Model
{
    protected $fillable = [
        'name',
        'base_url',
        'partner_id',
        'merchant_id',
        'private_key',
        'channel_id',
        'qris_channel_code',
    ];

    protected $hidden = [
        'private_key',
    ];

    public function getTable(): string
    {
        return config('faspay-test-lab.tables.merchants', 'faspay_test_lab_merchants');
    }

    protected function casts(): array
    {
        return [
            'private_key' => 'encrypted',
        ];
    }

    public function runs(): HasMany
    {
        return $this->hasMany(FaspayTestRun::class, 'faspay_merchant_id');
    }
}
