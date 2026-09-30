<?php

namespace Vonso\FaspayTestLab\Services;

use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;
use Vonso\FaspayTestLab\Models\FaspayMerchant;

class PaymentChannelInquiryService
{
    /**
     * @return array<int, array{code:string,name:string,prefix:string}>
     */
    public function channels(FaspayMerchant $merchant): array
    {
        $defaultChannels = [
            ['code' => '402', 'name' => 'Permata Virtual Account', 'prefix' => $merchant->partner_id.'1'],
            ['code' => '800', 'name' => 'BRI Virtual Account', 'prefix' => $merchant->partner_id.'2'],
            ['code' => '802', 'name' => 'Mandiri Virtual Account', 'prefix' => $merchant->partner_id.'001'],
            ['code' => '708', 'name' => 'Danamon Virtual Account', 'prefix' => $merchant->partner_id.'5'],
            ['code' => '825', 'name' => 'CIMB Virtual Account', 'prefix' => $merchant->partner_id.'4'],
            ['code' => '408', 'name' => 'Maybank Virtual Account', 'prefix' => $merchant->partner_id.'002'],
            ['code' => '818', 'name' => 'Sinarmas Virtual Account', 'prefix' => $merchant->partner_id.'4'],
        ];

        $configuredPrefixes = (array) config('faspay-test-lab.va_notification.channel_prefixes', []);
        if (empty($configuredPrefixes)) {
            $oldChannels = (array) config('faspay-test-lab.va_notification.channels', []);
            foreach ($oldChannels as $code => $data) {
                if (isset($data['prefix'])) {
                    $configuredPrefixes[(string) $code] = (string) $data['prefix'];
                }
            }
        }

        $prefixes = collect($defaultChannels)
            ->mapWithKeys(fn (array $c): array => [$c['code'] => $c['prefix']])
            ->merge($configuredPrefixes);

        $defaultList = collect($defaultChannels)->map(fn (array $c): array => [
            'code' => $c['code'],
            'name' => $c['name'],
            'prefix' => (string) $prefixes->get($c['code'], $c['prefix']),
        ])->all();

        if (! config('faspay-test-lab.payment_channel_inquiry.enabled', true)) {
            return $defaultList;
        }

        $userId = trim((string) config('faspay-test-lab.payment_channel_inquiry.user_id'));
        $password = (string) config('faspay-test-lab.payment_channel_inquiry.password');
        if ($userId === '' || $password === '') {
            return $defaultList;
        }

        $path = '/'.ltrim((string) config('faspay-test-lab.payment_channel_inquiry.path', '/cvr/100001/10'), '/');
        $url = rtrim($merchant->base_url, '/').$path;
        $body = [
            'request' => 'Request List of Payment Gateway',
            'merchant_id' => $merchant->merchant_id,
            'merchant' => $merchant->name,
            'signature' => sha1(md5($userId.$password)),
        ];

        try {
            $response = Http::connectTimeout(5)
                ->timeout(max(5, (int) config('faspay-test-lab.payment_channel_inquiry.timeout', 15)))
                ->acceptJson()
                ->asJson()
                ->post($url, $body);

            $responseBody = $response->json();
            if (! $response->successful() || ! is_array($responseBody)) {
                return $defaultList;
            }
            if (is_array($responseBody['response_error'] ?? null) || (string) ($responseBody['response_code'] ?? '') !== '00') {
                return $defaultList;
            }

            $channels = collect($responseBody['payment_channel'] ?? [])
                ->filter(fn (mixed $channel): bool => is_array($channel))
                ->map(function (array $channel) use ($prefixes): array {
                    $code = trim((string) ($channel['pg_code'] ?? ''));

                    return [
                        'code' => $code,
                        'name' => trim((string) ($channel['pg_name'] ?? $code)),
                        'prefix' => trim((string) $prefixes->get($code, '')),
                    ];
                })
                ->filter(fn (array $channel): bool => $channel['code'] !== '' && $channel['prefix'] !== '')
                ->unique('code')
                ->values()
                ->all();

            return ! empty($channels) ? $channels : $defaultList;
        } catch (Throwable) {
            return $defaultList;
        }
    }
}
