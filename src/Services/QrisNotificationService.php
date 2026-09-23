<?php

namespace Vonso\FaspayTestLab\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vonso\FaspayTestLab\Models\FaspayTestRun;

class QrisNotificationService
{
    public function __construct(private readonly FaspayInboundSignatureVerifier $signatureVerifier) {}

    public function handle(Request $request): JsonResponse
    {
        if (! config('faspay-test-lab.qris_notification.enabled', true)) {
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
            $this->signatureStatus($request, 'invalid');

            return $this->response('4015200', 'Unauthorized. [Signature]', 401);
        }
        $this->signatureStatus($request, 'valid');

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
        $configuredPath = (string) config('faspay-test-lab.qris_notification.path');

        return $this->signatureVerifier->verify($request, [
            '/'.ltrim($request->path(), '/'),
            $request->fullUrl(),
            '/v1.0/qr/qr-mpm-notify',
            '/notification/v1.0/qr/qr-mpm-notify',
            '/'.ltrim($configuredPath, '/'),
        ], 'faspay-test-lab.qris_notification');
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

    private function signatureStatus(Request $request, string $status): void
    {
        $request->attributes->get('faspay-test-lab.callback')?->update([
            'signature_status' => $status,
        ]);
    }
}