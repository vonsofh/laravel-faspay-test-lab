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
        $prefixes = collect(config('faspay-test-lab.va_notification.channel_prefixes', []))
            ->mapWithKeys(fn (mixed $prefix, mixed $code): array => [(string) $code => (string) $prefix]);

        if (! config('faspay-test-lab.payment_channel_inquiry.enabled', true)) {
            return [];
        }

        $userId = trim((string) config('faspay-test-lab.payment_channel_inquiry.user_id'));
        $password = (string) config('faspay-test-lab.payment_channel_inquiry.password');
        if ($userId === '' || $password === '') {
            throw new RuntimeException('Kredensial Payment Channel Inquiry belum dikonfigurasi.');
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
        } catch (Throwable $exception) {
            throw new RuntimeException('Faspay Payment Channel Inquiry tidak dapat dihubungi.', previous: $exception);
        }

        $responseBody = $response->json();
        if (! $response->successful() || ! is_array($responseBody)) {
            throw new RuntimeException("Faspay Payment Channel Inquiry merespons HTTP {$response->status()}.");
        }
        if (is_array($responseBody['response_error'] ?? null)) {
            throw new RuntimeException((string) ($responseBody['response_error']['response_desc'] ?? 'Payment Channel Inquiry ditolak Faspay.'));
        }
        if ((string) ($responseBody['response_code'] ?? '') !== '00') {
            throw new RuntimeException((string) ($responseBody['response_desc'] ?? 'Payment Channel Inquiry ditolak Faspay.'));
        }

        return collect($responseBody['payment_channel'] ?? [])
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
    }
}
