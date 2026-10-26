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
        'va_target_url',
        'va_inquiry_path',
        'va_payment_path',
        'va_partner_service_id',
        'va_test_customer_no',
        'va_paid_customer_no',
        'va_expired_customer_no',
        'va_test_amount',
    ];

    protected $hidden = [
        'private_key',
    ];

    public function getEffectiveVaTargetUrl(): string
    {
        return rtrim($this->va_target_url ?: config('app.url', 'http://localhost:8000'), '/');
    }

    public function getEffectiveVaInquiryPath(): string
    {
        return '/' . ltrim($this->va_inquiry_path ?: '/faspay/sandbox/v1.0/transfer-va/inquiry', '/');
    }

    public function getEffectiveVaPaymentPath(): string
    {
        return '/' . ltrim($this->va_payment_path ?: '/faspay/sandbox/v1.0/transfer-va/payment', '/');
    }

    public function getEffectiveVaPartnerServiceId(): string
    {
        return $this->va_partner_service_id ?: ($this->partner_id ? $this->partner_id . '1' : '370731');
    }

    public function getEffectiveVaCustomerNo(): string
    {
        return $this->va_test_customer_no ?: '2330370475';
    }

    public function getEffectiveVaAmount(): string
    {
        return $this->va_test_amount ?: '10000.00';
    }

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
