<?php

namespace Vonso\FaspayTestLab\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vonso\FaspayTestLab\Models\FaspayTestRun;

class QrisNotificationService
{
    private const ENDPOINT = '/v1.0/qr/qr-mpm-notify';

    public function handle(Request $request): JsonResponse
    {
        if (! config('faspay-test-lab.qris_notification.enabled', false)) {
            return $this->response('4045201', 'Transaction Not Found', 404);
        }

        $body = $request->json()->all();
        foreach (['originalPartnerReferenceNo', 'originalReferenceNo', 'amount', 'additionalInfo', 'latestTransactionStatus', 'transactionStatusDesc'] as $field) {
            if (! array_key_exists($field, $body)) {
                return $this->response('4005202', "Invalid Mandatory Field {$field}", 400);
            }
        }

        foreach (['X-TIMESTAMP', 'X-SIGNATURE', 'X-PARTNER-ID', 'X-EXTERNAL-ID', 'CHANNEL-ID'] as $header) {
            if (blank($request->header($header))) {
                return $this->response('4005202', "Invalid Mandatory Field {$header}", 400);
            }
        }

        if (! hash_equals(
            (string) $request->header('X-PARTNER-ID'),
            (string) data_get($body, 'additionalInfo.merchantId'),
        )) {
            return $this->response('4005200', 'Bad Request', 400);
        }

        if (! $this->validSignature($request)) {
            return $this->response('4015200', 'Unauthorized. [Signature]', 401);
        }

        $run = $this->matchingRun($body, (string) $request->header('X-PARTNER-ID'));
        if ($run === null) {
            return $this->response('4045201', 'Transaction Not Found', 404);
        }

        $successful = (string) $body['latestTransactionStatus'] === '00';
        $response = [
            'responseCode' => '2005200',
            'responseMessage' => 'Request has been processed successfully',
        ];

        if (! $successful) {
            return response()->json($response, 200, ['X-TIMESTAMP' => now('Asia/Jakarta')->format('Y-m-d\TH:i:sP')]);
        }

        $existing = collect($run->results)->firstWhere('test_no', '18.25');
        if (($existing['result'] ?? null) === 'PASS') {
            return response()->json($response, 200, ['X-TIMESTAMP' => now('Asia/Jakarta')->format('Y-m-d\TH:i:sP')]);
        }

        $result = [
            'test_no' => '18.25',
            'no' => '18.25',
            'service' => 'Payment Notification',
            'execution_type' => 'manual',
            'scenario' => 'Merchant membuat transaksi hingga pembayaran sukses. Saat menerima request notifikasi sukses dari Faspay, merchant mengembalikan response sesuai dokumentasi.',
            'expected_code' => '2005200',
            'expected_message' => 'Request has been processed successfully',
            'actual_code' => '2005200',
            'actual_message' => 'Request has been processed successfully',
            'passed' => true,
            'result' => 'PASS',
            'notes' => 'Notifikasi pembayaran QRIS diterima dari Faspay.',
            'request' => [
                'url' => $request->fullUrl(),
                'method' => 'POST',
                'headers' => $this->evidenceHeaders($request),
                'body' => $body,
            ],
            'response' => ['http_status' => 200, 'body' => $response],
            'metadata' => [
                'received_at' => now()->toIso8601String(),
                'originalReferenceNo' => (string) $body['originalReferenceNo'],
                'originalPartnerReferenceNo' => (string) $body['originalPartnerReferenceNo'],
                'latestTransactionStatus' => (string) $body['latestTransactionStatus'],
                'transactionStatusDesc' => (string) $body['transactionStatusDesc'],
                'paymentDate' => data_get($body, 'additionalInfo.paymentDate'),
            ],
        ];

        $results = collect($run->results)
            ->map(fn (array $item): array => (string) $item['test_no'] === '18.25' ? $result : $item)
            ->values()
            ->all();
        $run->update(['results' => $results]);

        return response()->json($response, 200, ['X-TIMESTAMP' => now('Asia/Jakarta')->format('Y-m-d\TH:i:sP')]);
    }

    private function matchingRun(array $body, string $partnerId): ?FaspayTestRun
    {
        return FaspayTestRun::query()
            ->where('service', 'qris')
            ->whereHas('merchant', fn ($query) => $query->where('partner_id', $partnerId))
            ->latest()
            ->limit(100)
            ->get()
            ->first(function (FaspayTestRun $run) use ($body): bool {
                $generated = collect($run->results)->firstWhere('test_no', '18.6');

                return hash_equals(
                    (string) data_get($generated, 'metadata.referenceNo'),
                    (string) $body['originalReferenceNo'],
                ) && hash_equals(
                    (string) data_get($generated, 'metadata.partnerReferenceNo'),
                    (string) $body['originalPartnerReferenceNo'],
                );
            });
    }

    private function validSignature(Request $request): bool
    {
        $publicKey = $this->publicKey();
        $signature = base64_decode((string) $request->header('X-SIGNATURE'), true);
        if ($publicKey === null || $signature === false) {
            return false;
        }

        $body = json_encode(
            $request->json()->all(),
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
        );
        $stringToSign = 'POST:'.self::ENDPOINT.':'.strtolower(hash('sha256', $body)).':'.$request->header('X-TIMESTAMP');

        return openssl_verify($stringToSign, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1;
    }

    private function publicKey(): mixed
    {
        $configured = config('faspay-test-lab.qris_notification.faspay_public_key');
        if (is_string($configured) && trim($configured) !== '') {
            $value = str_replace('\\n', "\n", $configured);

            return openssl_pkey_get_public($value);
        }

        $path = config('faspay-test-lab.qris_notification.faspay_public_key_path');
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $value = @file_get_contents($path);

        return is_string($value) ? openssl_pkey_get_public($value) : null;
    }

    private function evidenceHeaders(Request $request): array
    {
        return collect(['X-TIMESTAMP', 'X-SIGNATURE', 'X-PARTNER-ID', 'X-EXTERNAL-ID', 'CHANNEL-ID'])
            ->mapWithKeys(fn (string $header): array => [$header => $request->header($header)])
            ->all();
    }

    private function response(string $code, string $message, int $status): JsonResponse
    {
        return response()->json([
            'responseCode' => $code,
            'responseMessage' => $message,
        ], $status, ['X-TIMESTAMP' => now('Asia/Jakarta')->format('Y-m-d\TH:i:sP')]);
    }
}