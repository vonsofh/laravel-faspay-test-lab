<?php

namespace Vonso\FaspayTestLab\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaspayCallback extends Model
{
    protected $fillable = [
        'faspay_test_lab_va_account_id',
        'service',
        'request_method',
        'request_path',
        'external_id',
        'reference_no',
        'signature_status',
        'http_status',
        'response_code',
        'request_headers',
        'request_body',
        'response_body',
        'error',
        'client_ip',
        'content_type',
        'user_agent',
    ];

    public function getTable(): string
    {
        return config('faspay-test-lab.tables.callbacks', 'faspay_test_lab_callbacks');
    }

    protected function casts(): array
    {
        return [
            'request_headers' => 'array',
            'request_body' => 'array',
            'response_body' => 'array',
        ];
    }

    public function vaAccount(): BelongsTo
    {
        return $this->belongsTo(FaspayVaAccount::class, 'faspay_test_lab_va_account_id');
    }
}
