<?php

namespace Vonso\FaspayTestLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FaspayVaAccount extends Model
{
    protected $fillable = [
        'faspay_merchant_id',
        'channel_code',
        'channel_name',
        'partner_service_id',
        'customer_no',
        'virtual_account_no',
        'display_name',
        'amount_minor',
        'active',
    ];

    public function getTable(): string
    {
        return config('faspay-test-lab.tables.va_accounts', 'faspay_test_lab_va_accounts');
    }

    protected function casts(): array
    {
        return [
            'amount_minor' => 'integer',
            'active' => 'boolean',
        ];
    }

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(FaspayMerchant::class, 'faspay_merchant_id');
    }

    public function callbacks(): HasMany
    {
        return $this->hasMany(FaspayCallback::class, 'faspay_test_lab_va_account_id');
    }
}
