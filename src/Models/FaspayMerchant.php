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
        if (filled($this->va_target_url)) {
            return rtrim($this->va_target_url, '/');
        }

        if (function_exists('request') && request() && request()->root()) {
            return rtrim(request()->root(), '/');
        }

        return rtrim(config('app.url', 'http://localhost:8000'), '/');
    }

    public function getEffectiveVaInquiryPath(): string
    {
        if (filled($this->va_inquiry_path)) {
            return '/' . ltrim($this->va_inquiry_path, '/');
        }

        if (\Illuminate\Support\Facades\Route::has('faspay.va.inquiry')) {
            return route('faspay.va.inquiry', ['mode' => 'sandbox'], false);
        }

        if (\Illuminate\Support\Facades\Route::has('faspay-test-lab.va.inquiry')) {
            return route('faspay-test-lab.va.inquiry', [], false);
        }

        return '/' . ltrim(config('faspay-test-lab.va_inquiry.path', '/faspay/sandbox/v1.0/transfer-va/inquiry'), '/');
    }

    public function getEffectiveVaPaymentPath(): string
    {
        if (filled($this->va_payment_path)) {
            return '/' . ltrim($this->va_payment_path, '/');
        }

        if (\Illuminate\Support\Facades\Route::has('faspay.va.payment')) {
            return route('faspay.va.payment', ['mode' => 'sandbox'], false);
        }

        if (\Illuminate\Support\Facades\Route::has('faspay-test-lab.va.payment')) {
            return route('faspay-test-lab.va.payment', [], false);
        }

        return '/' . ltrim(config('faspay-test-lab.va_notification.payment_path', '/faspay/sandbox/v1.0/transfer-va/payment'), '/');
    }

    public function resolveActiveSandboxAccount(): ?object
    {
        // 1. Host application sandbox account (e.g. faspay_sandbox_accounts table in sikeu)
        if (\Illuminate\Support\Facades\Schema::hasTable('faspay_sandbox_accounts')) {
            $account = \Illuminate\Support\Facades\DB::table('faspay_sandbox_accounts')
                ->where('active', 1)
                ->latest('id')
                ->first();

            if ($account) {
                return (object) [
                    'partner_service_id' => (string) $account->partner_service_id,
                    'customer_no' => (string) $account->customer_no,
                    'virtual_account_no' => (string) $account->virtual_account_no,
                    'amount' => number_format(((int) $account->amount_minor) / 100, 2, '.', ''),
                    'display_name' => (string) ($account->display_name ?? 'FASPAY SANDBOX'),
                ];
            }
        }

        // 2. Package test lab sandbox account (faspay_test_lab_va_accounts)
        $testLabTable = config('faspay-test-lab.tables.va_accounts', 'faspay_test_lab_va_accounts');
        if (\Illuminate\Support\Facades\Schema::hasTable($testLabTable)) {
            $account = \Illuminate\Support\Facades\DB::table($testLabTable)
                ->where('faspay_merchant_id', $this->id)
                ->where('active', 1)
                ->latest('id')
                ->first();

            if ($account) {
                return (object) [
                    'partner_service_id' => (string) $account->partner_service_id,
                    'customer_no' => (string) $account->customer_no,
                    'virtual_account_no' => (string) $account->virtual_account_no,
                    'amount' => number_format(((int) $account->amount_minor) / 100, 2, '.', ''),
                    'display_name' => (string) ($account->display_name ?? 'FASPAY SANDBOX'),
                ];
            }
        }

        return null;
    }

    public function getEffectiveVaPartnerServiceId(): string
    {
        if (filled($this->va_partner_service_id)) {
            return $this->va_partner_service_id;
        }

        $account = $this->resolveActiveSandboxAccount();
        if ($account && ! empty($account->partner_service_id)) {
            return $account->partner_service_id;
        }

        return $this->partner_id ? $this->partner_id . '1' : '370731';
    }

    public function getEffectiveVaCustomerNo(): string
    {
        if (filled($this->va_test_customer_no)) {
            return $this->va_test_customer_no;
        }

        $account = $this->resolveActiveSandboxAccount();
        if ($account && ! empty($account->customer_no)) {
            return $account->customer_no;
        }

        return '8385533750';
    }

    public function getEffectiveVaAmount(): string
    {
        if (filled($this->va_test_amount)) {
            return $this->va_test_amount;
        }

        $account = $this->resolveActiveSandboxAccount();
        if ($account && ! empty($account->amount)) {
            return $account->amount;
        }

        return '10000.00';
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
