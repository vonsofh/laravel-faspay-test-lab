<?php

namespace Vonso\FaspayTestLab\Services;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Vonso\FaspayTestLab\Models\FaspayMerchant;
use Vonso\FaspayTestLab\Models\FaspayVaAccount;

class VaSandboxAccountService
{
    public function create(
        FaspayMerchant $merchant,
        string $channelCode,
        string $displayName,
        int $amountMinor,
    ): FaspayVaAccount {
        $channel = config("faspay-test-lab.va_notification.channels.{$channelCode}");
        $prefix = preg_replace('/\D+/', '', (string) data_get($channel, 'prefix')) ?? '';
        if (! in_array(strlen($prefix), [6, 8], true)) {
            throw new RuntimeException('Prefix VA sandbox harus 6 atau 8 digit.');
        }

        return DB::transaction(function () use ($merchant, $channelCode, $channel, $prefix, $displayName, $amountMinor): FaspayVaAccount {
            FaspayVaAccount::query()
                ->where('faspay_merchant_id', $merchant->id)
                ->where('active', true)
                ->lockForUpdate()
                ->update(['active' => false]);

            $customerLength = 16 - strlen($prefix);
            foreach (range(1, 20) as $attempt) {
                $body = str_pad((string) random_int(1, (10 ** min($customerLength - 1, 9)) - 1), $customerLength - 1, '0', STR_PAD_LEFT);
                $body = substr($body, -($customerLength - 1));
                $customerNo = $body.$this->luhnCheckDigit($body);
                $virtualAccountNo = $prefix.$customerNo;

                if (! FaspayVaAccount::query()->where('virtual_account_no', $virtualAccountNo)->exists()) {
                    return FaspayVaAccount::create([
                        'faspay_merchant_id' => $merchant->id,
                        'channel_code' => $channelCode,
                        'channel_name' => (string) data_get($channel, 'name', $channelCode),
                        'partner_service_id' => $prefix,
                        'customer_no' => $customerNo,
                        'virtual_account_no' => $virtualAccountNo,
                        'display_name' => mb_strtoupper(trim($displayName)),
                        'amount_minor' => $amountMinor,
                        'active' => true,
                    ]);
                }
            }

            throw new RuntimeException('Tidak dapat membuat nomor VA sandbox yang unik.');
        });
    }

    private function luhnCheckDigit(string $digits): string
    {
        $sum = 0;
        $double = true;

        for ($index = strlen($digits) - 1; $index >= 0; $index--) {
            $digit = (int) $digits[$index];
            if ($double && ($digit *= 2) > 9) {
                $digit -= 9;
            }
            $sum += $digit;
            $double = ! $double;
        }

        return (string) ((10 - ($sum % 10)) % 10);
    }
}
