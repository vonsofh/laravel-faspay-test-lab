<?php

namespace Vonso\FaspayTestLab\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Vonso\FaspayTestLab\Models\FaspayVaAccount;

class VaNotificationService
{
    public function __construct(private readonly FaspayInboundSignatureVerifier $signatureVerifier) {}

    public function inquiry(Request $request): JsonResponse
    {
        return $this->handle($request, 'inquiry', '24', 'inquiryRequestId');
    }

    public function payment(Request $request): JsonResponse
    {
        return $this->handle($request, 'payment', '25', 'paymentRequestId');
    }

    private function handle(Request $request, string $service, string $serviceCode, string $requestIdField): JsonResponse
    {
        if (! config('faspay-test-lab.va_notification.enabled', true)) {
            return $this->respond($request, $service, $serviceCode, null, '404'.$serviceCode.'11', 'Invalid Virtual Account', 404, 'not_checked');
        }

        $body = $request->json()->all();
        foreach (['partnerServiceId', 'customerNo', 'virtualAccountNo', $requestIdField] as $field) {
            if ($this->field($body, $field) === null) {
                return $this->respond($request, $service, $serviceCode, null, '400'.$serviceCode.'02', "Invalid Mandatory Field {$field}", 400, 'not_checked');
            }
        }
        if ($service === 'payment' && (! is_array($body['paidAmount'] ?? null) || blank(data_get($body, 'paidAmount.value')) || blank(data_get($body, 'paidAmount.currency')))) {
            return $this->respond($request, $service, $serviceCode, null, '4002502', 'Invalid Mandatory Field paidAmount', 400, 'not_checked');
        }

        foreach (['X-TIMESTAMP', 'X-SIGNATURE', 'X-PARTNER-ID', 'X-EXTERNAL-ID', 'CHANNEL-ID'] as $header) {
            if (blank($request->header($header))) {
                return $this->respond($request, $service, $serviceCode, null, '400'.$serviceCode.'02', "Invalid Mandatory Field {$header}", 400, 'not_checked');
            }
        }

        $account = $this->account($body, (string) $request->header('X-PARTNER-ID'));
        if ($account === null) {
            return $this->respond($request, $service, $serviceCode, null, '404'.$serviceCode.'11', 'Invalid Virtual Account', 404, 'not_checked');
        }

        $configuredPath = (string) config("faspay-test-lab.va_notification.{$service}_path");
        $validSignature = $this->signatureVerifier->verify($request, [
            '/'.ltrim($request->path(), '/'),
            $request->fullUrl(),
            '/v1.0/transfer-va/'.$service,
            '/notification/v1.0/transfer-va/'.$service,
            '/'.ltrim($configuredPath, '/'),
        ], 'faspay-test-lab.va_notification');
        if (! $validSignature) {
            return $this->respond($request, $service, $serviceCode, $account, '401'.$serviceCode.'00', 'Unauthorized. [Signature]', 401, 'invalid');
        }

        if ($service === 'payment') {
            $value = (string) data_get($body, 'paidAmount.value');
            $currency = strtoupper((string) data_get($body, 'paidAmount.currency'));
            if ($currency !== 'IDR' || preg_match('/^\d+(\.\d{1,2})?$/', $value) !== 1) {
                return $this->respond($request, $service, $serviceCode, $account, '4002501', 'Invalid Field Format paidAmount', 400, 'valid');
            }
            if ($this->amountMinor($value) !== $account->amount_minor) {
                return $this->respond($request, $service, $serviceCode, $account, '4042513', 'Invalid Amount', 404, 'valid');
            }
        }

        $data = [
            'partnerServiceId' => str_pad($account->partner_service_id, 8, ' ', STR_PAD_LEFT),
            'customerNo' => $account->customer_no,
            'virtualAccountNo' => str_pad($account->partner_service_id, 8, ' ', STR_PAD_LEFT).$account->customer_no,
            'virtualAccountName' => $account->display_name,
            $requestIdField => (string) $body[$requestIdField],
        ];
        $amountKey = $service === 'payment' ? 'paidAmount' : 'totalAmount';
        $data[$amountKey] = [
            'value' => number_format($account->amount_minor / 100, 2, '.', ''),
            'currency' => 'IDR',
        ];

        return $this->respond($request, $service, $serviceCode, $account, '200'.$serviceCode.'00', 'Successful', 200, 'valid', [
            'responseCode' => '200'.$serviceCode.'00',
            'responseMessage' => 'Successful',
            'virtualAccountData' => $data,
        ]);
    }

    private function account(array $body, string $partnerId): ?FaspayVaAccount
    {
        $virtualAccountNo = preg_replace('/\D+/', '', (string) ($body['virtualAccountNo'] ?? '')) ?? '';
        $partnerServiceId = preg_replace('/\D+/', '', (string) ($body['partnerServiceId'] ?? '')) ?? '';
        $customerNo = preg_replace('/\D+/', '', (string) ($body['customerNo'] ?? '')) ?? '';

        return FaspayVaAccount::query()
            ->with('merchant')
            ->where('active', true)
            ->where('virtual_account_no', $virtualAccountNo)
            ->where('partner_service_id', $partnerServiceId)
            ->where('customer_no', $customerNo)
            ->whereHas('merchant', fn ($query) => $query->where('partner_id', $partnerId))
            ->first();
    }

    private function field(array $body, string $key): ?string
    {
        $value = $body[$key] ?? null;
        if (! is_scalar($value)) {
            return null;
        }
        $value = preg_replace('/\s+/', '', (string) $value) ?? '';

        return $value === '' ? null : $value;
    }

    private function amountMinor(string $value): int
    {
        [$rupiah, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        return ((int) $rupiah * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function respond(
        Request $request,
        string $service,
        string $serviceCode,
        ?FaspayVaAccount $account,
        string $code,
        string $message,
        int $status,
        string $signatureStatus,
        ?array $responseBody = null,
    ): JsonResponse {
        $responseBody ??= ['responseCode' => $code, 'responseMessage' => $message];
        $request->attributes->get('faspay-test-lab.callback')?->update([
            'faspay_test_lab_va_account_id' => $account?->id,
            'signature_status' => $signatureStatus,
        ]);

        return response()->json($responseBody, $status, ['X-TIMESTAMP' => now()->toIso8601String()], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

}
