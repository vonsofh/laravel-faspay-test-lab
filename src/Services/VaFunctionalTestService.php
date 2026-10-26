<?php

namespace Vonso\FaspayTestLab\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Vonso\FaspayTestLab\Models\FaspayMerchant;

class VaFunctionalTestService
{
    public function __construct(
        private readonly FaspaySnapSigner $signer,
    ) {}

    public function runSuite(FaspayMerchant $merchant): array
    {
        return array_map(
            fn (array $case): array => $this->runCase($merchant, $case),
            config('faspay-test-lab.va_cases', []),
        );
    }

    public function runCase(FaspayMerchant $merchant, array $case): array
    {
        if ($case['execution_type'] !== 'automated') {
            return $this->nonAutomatedResult($case);
        }

        return match ($case['handler']) {
            'duplicate_external_id' => $this->runDuplicateExternalIdCase($merchant, $case),
            'valid_va_inquiry', 'paid_va_inquiry', 'expired_va_inquiry', 'unregistered_va_inquiry',
            'invalid_signature', 'missing_mandatory_field', 'invalid_field_format' => $this->runInquiryCase($merchant, $case),
            'valid_va_payment', 'unregistered_va_payment', 'invalid_amount_payment' => $this->runPaymentCase($merchant, $case),
            default => $this->runInquiryCase($merchant, $case),
        };
    }

    private function runInquiryCase(FaspayMerchant $merchant, array $case, ?string $forcedExternalId = null): array
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $timestamp = $now->format('Y-m-d\\TH:i:sP');
        $externalId = $forcedExternalId ?? $this->numericId(29);
        $inquiryRequestId = $this->numericId(22);

        $partnerServiceId = $merchant->getEffectiveVaPartnerServiceId();
        $paddedPartnerServiceId = str_pad($partnerServiceId, 8, ' ', STR_PAD_LEFT);

        $customerNo = match ($case['handler']) {
            'paid_va_inquiry' => $merchant->va_paid_customer_no ?: '9999990001',
            'expired_va_inquiry' => $merchant->va_expired_customer_no ?: '9999990002',
            'unregistered_va_inquiry' => '9999999999',
            default => $merchant->getEffectiveVaCustomerNo(),
        };

        $virtualAccountNo = $paddedPartnerServiceId.$customerNo;

        $body = [
            'partnerServiceId' => $paddedPartnerServiceId,
            'customerNo' => $customerNo,
            'virtualAccountNo' => $virtualAccountNo,
            'inquiryRequestId' => $inquiryRequestId,
            'channelCode' => '402',
            'trxDateInit' => $timestamp,
        ];

        $targetPath = $merchant->getEffectiveVaInquiryPath();
        $targetUrl = $merchant->getEffectiveVaTargetUrl().$targetPath;

        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $signed = $this->signer->sign('POST', $targetPath, $bodyJson, $timestamp, $merchant->private_key);

        $headers = [
            'Content-Type' => 'application/json',
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $case['handler'] === 'invalid_signature' ? 'INVALID_SIGNATURE' : $signed['signature'],
            'X-PARTNER-ID' => $merchant->partner_id,
            'X-EXTERNAL-ID' => $case['handler'] === 'invalid_field_format' ? 'externalId' : $externalId,
            'CHANNEL-ID' => $merchant->channel_id,
        ];

        if ($case['handler'] === 'missing_mandatory_field') {
            unset($headers['X-TIMESTAMP']);
        }

        $response = Http::connectTimeout(5)->timeout(30)->withHeaders($headers)
            ->withBody($bodyJson, 'application/json')
            ->post($targetUrl);

        $responseJson = $response->json() ?: [];
        $actualCode = (string) ($responseJson['responseCode'] ?? $response->status());
        $actualMessage = (string) ($responseJson['responseMessage'] ?? '');

        $passed = $this->matchesExpectedResponse($case, $actualCode, $actualMessage);

        return [
            'test_no' => $case['no'],
            'no' => $case['no'],
            'service' => $case['service'],
            'execution_type' => $case['execution_type'],
            'scenario' => $case['scenario'],
            'expected_code' => $case['expected_code'],
            'expected_message' => $case['expected_message'] ?? '',
            'request' => [
                'url' => $targetUrl,
                'method' => 'POST',
                'headers' => $headers,
                'body' => $body,
                'body_json' => $bodyJson,
            ],
            'response' => [
                'http_status' => $response->status(),
                'body' => $responseJson ?: $response->body(),
            ],
            'actual_code' => $actualCode,
            'actual_message' => $actualMessage,
            'passed' => $passed,
            'result' => $passed ? 'PASS' : 'FAIL',
            'notes' => $case['notes'],
            'metadata' => [
                'virtualAccountNo' => $virtualAccountNo,
                'customerNo' => $customerNo,
            ],
        ];
    }

    private function runPaymentCase(FaspayMerchant $merchant, array $case): array
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $timestamp = $now->format('Y-m-d\\TH:i:sP');
        $externalId = $this->numericId(29);
        $paymentRequestId = $this->numericId(22);
        $referenceNo = $this->numericId(16);

        $partnerServiceId = $merchant->getEffectiveVaPartnerServiceId();
        $paddedPartnerServiceId = str_pad($partnerServiceId, 8, ' ', STR_PAD_LEFT);

        $customerNo = match ($case['handler']) {
            'unregistered_va_payment' => '9999999999',
            default => $merchant->getEffectiveVaCustomerNo(),
        };

        $amountValue = match ($case['handler']) {
            'invalid_amount_payment' => '500.00',
            default => number_format((float) $merchant->getEffectiveVaAmount(), 2, '.', ''),
        };

        $virtualAccountNo = $paddedPartnerServiceId.$customerNo;

        $body = [
            'partnerServiceId' => $paddedPartnerServiceId,
            'customerNo' => $customerNo,
            'virtualAccountNo' => $virtualAccountNo,
            'paymentRequestId' => $paymentRequestId,
            'paidAmount' => [
                'value' => $amountValue,
                'currency' => 'IDR',
            ],
            'trxDateTime' => $timestamp,
            'referenceNo' => $referenceNo,
        ];

        $targetPath = $merchant->getEffectiveVaPaymentPath();
        $targetUrl = $merchant->getEffectiveVaTargetUrl().$targetPath;

        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $signed = $this->signer->sign('POST', $targetPath, $bodyJson, $timestamp, $merchant->private_key);

        $headers = [
            'Content-Type' => 'application/json',
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signed['signature'],
            'X-PARTNER-ID' => $merchant->partner_id,
            'X-EXTERNAL-ID' => $externalId,
            'CHANNEL-ID' => $merchant->channel_id,
        ];

        $response = Http::connectTimeout(5)->timeout(30)->withHeaders($headers)
            ->withBody($bodyJson, 'application/json')
            ->post($targetUrl);

        $responseJson = $response->json() ?: [];
        $actualCode = (string) ($responseJson['responseCode'] ?? $response->status());
        $actualMessage = (string) ($responseJson['responseMessage'] ?? '');

        $passed = $this->matchesExpectedResponse($case, $actualCode, $actualMessage);

        return [
            'test_no' => $case['no'],
            'no' => $case['no'],
            'service' => $case['service'],
            'execution_type' => $case['execution_type'],
            'scenario' => $case['scenario'],
            'expected_code' => $case['expected_code'],
            'expected_message' => $case['expected_message'] ?? '',
            'request' => [
                'url' => $targetUrl,
                'method' => 'POST',
                'headers' => $headers,
                'body' => $body,
                'body_json' => $bodyJson,
            ],
            'response' => [
                'http_status' => $response->status(),
                'body' => $responseJson ?: $response->body(),
            ],
            'actual_code' => $actualCode,
            'actual_message' => $actualMessage,
            'passed' => $passed,
            'result' => $passed ? 'PASS' : 'FAIL',
            'notes' => $case['notes'],
            'metadata' => [
                'virtualAccountNo' => $virtualAccountNo,
                'customerNo' => $customerNo,
                'paidAmount' => $amountValue,
            ],
        ];
    }

    private function runDuplicateExternalIdCase(FaspayMerchant $merchant, array $case): array
    {
        $externalId = $this->numericId(29);
        $setupCase = array_merge($case, [
            'handler' => 'valid_va_inquiry',
            'expected_code' => '2002400',
            'expected_message' => null,
        ]);

        $setup = $this->runInquiryCase($merchant, $setupCase, $externalId);
        $result = $this->runInquiryCase($merchant, array_merge($case, ['handler' => 'none']), $externalId);

        $passed = $setup['passed'] && $result['passed'];
        $result['passed'] = $passed;
        $result['result'] = $passed ? 'PASS' : 'FAIL';
        $result['request'] = [$setup['request'], $result['request']];
        $result['response'] = [$setup['response'], $result['response']];
        $result['metadata'] = ['external_id' => $externalId];

        return $result;
    }

    private function nonAutomatedResult(array $case): array
    {
        $result = match ($case['execution_type']) {
            'manual' => 'MANUAL',
            default => 'N/A',
        };

        return [
            'test_no' => $case['no'],
            'no' => $case['no'],
            'service' => $case['service'],
            'execution_type' => $case['execution_type'],
            'scenario' => $case['scenario'],
            'expected_code' => $case['expected_code'],
            'expected_message' => $case['expected_message'] ?? '',
            'request' => null,
            'response' => null,
            'actual_code' => null,
            'actual_message' => null,
            'passed' => null,
            'result' => $result,
            'notes' => $case['notes'],
            'metadata' => [],
        ];
    }

    private function matchesExpectedResponse(array $case, string $actualCode, string $actualMessage): bool
    {
        $expectedCode = (string) $case['expected_code'];

        $codeMatches = $actualCode === $expectedCode
            || (str_contains($expectedCode, 'xx') && preg_match('/^'.str_replace('xx', '\d{2}', $expectedCode).'$/', $actualCode) === 1);

        $expectedMessage = $case['expected_message'] ?? null;
        if (blank($expectedMessage)) {
            return $codeMatches;
        }

        $normActual = $this->normalizeResponseMessage($actualMessage);
        $normExpected = $this->normalizeResponseMessage($expectedMessage);

        $messageMatches = $normActual === $normExpected
            || str_contains($normActual, $normExpected)
            || str_contains($normExpected, $normActual)
            || (str_contains($normExpected, 'mandatory') && str_contains($normActual, 'mandatory'))
            || (str_contains($normExpected, 'unauthorized') && str_contains($normActual, 'unauthorized'));

        return $codeMatches && $messageMatches;
    }

    private function normalizeResponseMessage(?string $message): string
    {
        return Str::of($message ?? '')
            ->replace(['.', '[', ']', '{', '}', '-', '_'], ' ')
            ->squish()
            ->lower()
            ->toString();
    }

    private function numericId(int $length): string
    {
        $seed = now('Asia/Jakarta')->format('YmdHisv').random_int(100000000, 999999999);
        $digits = preg_replace('/\\D/', '', $seed);

        if (strlen($digits) >= $length) {
            return substr($digits, 0, $length);
        }

        return str_pad($digits, $length, (string) random_int(0, 9));
    }
}
