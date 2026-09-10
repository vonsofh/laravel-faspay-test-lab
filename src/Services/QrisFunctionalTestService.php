<?php

namespace Vonso\FaspayTestLab\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Vonso\FaspayTestLab\Models\FaspayMerchant;

class QrisFunctionalTestService
{
    private const GENERATE_ENDPOINT = '/v1.0/qr/qr-mpm-generate';

    private const QUERY_ENDPOINT = '/v1.0/qr/qr-mpm-query';

    public function __construct(
        private readonly FaspaySnapSigner $signer,
    ) {}

    public function runSuite(FaspayMerchant $merchant): array
    {
        return array_map(fn (array $case): array => $this->runCase($merchant, $case), config('faspay-test-lab.qris_cases', []));
    }

    public function runCase(FaspayMerchant $merchant, array $case): array
    {
        if ($case['execution_type'] !== 'automated') {
            return $this->nonAutomatedResult($case);
        }

        return match ($case['handler']) {
            'duplicate_external_id' => $this->runDuplicateExternalIdCase($merchant, $case),
            'query_pending' => $this->runPendingQueryCase($merchant, $case),
            'query_not_found' => $this->runNotFoundQueryCase($merchant, $case),
            default => $this->runGenerateCase($merchant, $case),
        };
    }

    public function buildQrisQueryBody(
        string $originalReferenceNo,
        string $originalPartnerReferenceNo,
        FaspayMerchant $merchant,
    ): array {
        return [
            'originalReferenceNo' => $originalReferenceNo,
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
            'serviceCode' => '47',
            'merchantId' => $merchant->partner_id,
            'additionalInfo' => [
                'channelCode' => $merchant->qris_channel_code,
            ],
        ];
    }

    public function checkPayment(
        FaspayMerchant $merchant,
        string $originalReferenceNo,
        string $originalPartnerReferenceNo,
    ): array {
        $case = collect(config('faspay-test-lab.qris_cases'))->firstWhere('no', '18.12');
        $result = $this->sendQuery($merchant, $case, $originalReferenceNo, $originalPartnerReferenceNo);
        $status = data_get($result, 'response.body.latestTransactionStatus');
        $isSuccessfulQuery = $result['actual_code'] === '2005100';
        $result['payment_status'] = match (true) {
            $status === '00' => 'PAYMENT DETECTED',
            $isSuccessfulQuery && in_array($status, ['01', '03'], true) => 'WAITING FOR PAYMENT',
            default => 'CHECK FAILED',
        };
        $result['passed'] = $isSuccessfulQuery && $status === '00';
        $result['result'] = match (true) {
            $result['passed'] => 'PASS',
            $isSuccessfulQuery && in_array($status, ['01', '03'], true) => 'WAITING',
            default => 'FAIL',
        };
        $result['metadata'] = [
            'latestTransactionStatus' => $status,
            'transactionStatusDesc' => data_get($result, 'response.body.transactionStatusDesc'),
            'paidTime' => data_get($result, 'response.body.paidTime'),
            'paymentReff' => data_get($result, 'response.body.paymentReff'),
            'originalReferenceNo' => $originalReferenceNo,
            'originalPartnerReferenceNo' => $originalPartnerReferenceNo,
        ];

        return $result;
    }

    public function runSuccessfulQuery(
        FaspayMerchant $merchant,
        string $originalReferenceNo,
        string $originalPartnerReferenceNo,
    ): array {
        $case = collect(config('faspay-test-lab.qris_cases'))->firstWhere('no', '18.12');
        $result = $this->sendQuery($merchant, $case, $originalReferenceNo, $originalPartnerReferenceNo);
        $result['passed'] = $result['actual_code'] === '2005100'
            && data_get($result, 'response.body.latestTransactionStatus') === '00';
        $result['result'] = $result['passed'] ? 'PASS' : 'FAIL';

        return $result;
    }

    private function runGenerateCase(FaspayMerchant $merchant, array $case, ?string $forcedExternalId = null): array
    {
        $now = CarbonImmutable::now('Asia/Jakarta');
        $timestamp = $now->format('Y-m-d\\TH:i:sP');
        $externalId = $forcedExternalId ?? $this->numericId(29);
        $partnerReferenceNo = $this->numericId(22);

        $body = [
            'partnerReferenceNo' => $partnerReferenceNo,
            'amount' => [
                'value' => '1800.00',
                'currency' => 'IDR',
            ],
            'merchantId' => $merchant->partner_id,
            'validityPeriod' => $now->addHours(24)->format('Y-m-d\\TH:i:sP'),
            'additionalInfo' => [
                'billDate' => $now->format('Y-m-d\\TH:i:sP'),
                'billDescription' => 'Payment #12345678',
                'channelCode' => $merchant->qris_channel_code,
                'phoneNo' => '082123456789',
            ],
        ];

        if ($case['handler'] === 'invalid_merchant') {
            $body['merchantId'] = '99999';
        }

        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $signed = $this->signer->sign(
            'POST',
            self::GENERATE_ENDPOINT,
            $bodyJson,
            $timestamp,
            $merchant->private_key,
        );

        $headers = [
            'Content-Type' => 'application/json',
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $case['handler'] === 'invalid_signature' ? 'INVALID_SIGNATURE' : $signed['signature'],
            'X-PARTNER-ID' => $merchant->partner_id,
            'X-EXTERNAL-ID' => $externalId,
            'CHANNEL-ID' => $merchant->channel_id,
        ];

        if ($case['handler'] === 'invalid_external_id') {
            $headers['X-EXTERNAL-ID'] = 'externalId';
        }
        if ($case['handler'] === 'missing_timestamp') {
            unset($headers['X-TIMESTAMP']);
        }

        $url = rtrim($merchant->base_url, '/').self::GENERATE_ENDPOINT;

        $response = Http::connectTimeout(5)->timeout(30)->withHeaders($headers)
            ->withBody($bodyJson, 'application/json')
            ->post($url);

        $responseJson = $response->json() ?: [];
        $actualCode = (string) ($responseJson['responseCode'] ?? $response->status());
        $actualMessage = (string) ($responseJson['responseMessage'] ?? '');

        $pass = $this->matchesExpectedResponse($case, $actualCode, $actualMessage);
        if ($pass && $case['handler'] === 'generate_success') {
            $pass = filled(data_get($responseJson, 'referenceNo'))
                && filled(data_get($responseJson, 'partnerReferenceNo'))
                && filled(data_get($responseJson, 'qrContent'));
        }

        $metadata = [];
        if ($case['handler'] === 'generate_success') {
            $metadata = [
                'referenceNo' => data_get($responseJson, 'referenceNo'),
                'partnerReferenceNo' => data_get($responseJson, 'partnerReferenceNo'),
                'qrContent' => data_get($responseJson, 'qrContent'),
                'qrUrl' => data_get($responseJson, 'qrUrl'),
                'qrImageUrl' => data_get($responseJson, 'additionalInfo.qrImageUrl'),
                'amount' => data_get($body, 'amount.value'),
                'merchantId' => $merchant->partner_id,
            ];
        }

        return [
            'test_no' => $case['no'],
            'no' => $case['no'],
            'service' => $case['service'],
            'execution_type' => $case['execution_type'],
            'scenario' => $case['scenario'],
            'expected_code' => $case['expected_code'],
            'expected_message' => $case['expected_message'] ?? ($case['expected_message_contains'] ?? ''),
            'request' => [
                'url' => $url,
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
            'passed' => $pass,
            'result' => $pass ? 'PASS' : 'FAIL',
            'notes' => $case['notes'],
            'metadata' => $metadata,
        ];
    }

    private function runDuplicateExternalIdCase(FaspayMerchant $merchant, array $case): array
    {
        $externalId = $this->numericId(29);
        $setupCase = array_merge($case, [
            'handler' => 'generate_success',
            'expected_code' => '2004700',
            'expected_message' => null,
        ]);
        $setup = $this->runGenerateCase($merchant, $setupCase, $externalId);
        $result = $this->runGenerateCase($merchant, array_merge($case, ['handler' => 'none']), $externalId);
        $result['passed'] = $setup['passed'] && $result['passed'];
        $result['result'] = $result['passed'] ? 'PASS' : 'FAIL';
        $result['request'] = [$setup['request'], $result['request']];
        $result['response'] = [$setup['response'], $result['response']];
        $result['metadata'] = ['external_id' => $externalId];

        return $result;
    }

    private function runPendingQueryCase(FaspayMerchant $merchant, array $case): array
    {
        $generateCase = array_merge($case, [
            'handler' => 'generate_success',
            'expected_code' => '2004700',
            'expected_message' => null,
        ]);
        $generate = $this->runGenerateCase($merchant, $generateCase);
        $referenceNo = data_get($generate, 'response.body.referenceNo');
        $partnerReferenceNo = data_get($generate, 'response.body.partnerReferenceNo');

        if (! $generate['passed'] || blank($referenceNo) || blank($partnerReferenceNo)) {
            $generate['test_no'] = $case['no'];
            $generate['no'] = $case['no'];
            $generate['service'] = $case['service'];
            $generate['scenario'] = $case['scenario'];
            $generate['expected_code'] = $case['expected_code'];
            $generate['expected_message'] = $case['expected_message'] ?? '';
            $generate['passed'] = false;
            $generate['result'] = 'FAIL';
            $generate['metadata'] = ['error' => 'Generate prerequisite tidak menghasilkan reference yang diperlukan.'];

            return $generate;
        }

        $query = $this->sendQuery($merchant, $case, (string) $referenceNo, (string) $partnerReferenceNo);
        $query['request'] = [$generate['request'], $query['request']];
        $query['response'] = [$generate['response'], $query['response']];
        $query['metadata'] = ['latest_transaction_status' => data_get($query['response'][1], 'body.latestTransactionStatus')];

        return $query;
    }

    private function runNotFoundQueryCase(FaspayMerchant $merchant, array $case): array
    {
        return $this->sendQuery($merchant, $case, $this->nonexistentReferenceNo(), $this->numericId(22));
    }

    private function sendQuery(
        FaspayMerchant $merchant,
        array $case,
        string $originalReferenceNo,
        string $originalPartnerReferenceNo,
    ): array {
        $timestamp = CarbonImmutable::now('Asia/Jakarta')->format('Y-m-d\\TH:i:sP');
        $body = $this->buildQrisQueryBody($originalReferenceNo, $originalPartnerReferenceNo, $merchant);
        $bodyJson = json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        $signed = $this->signer->sign('POST', self::QUERY_ENDPOINT, $bodyJson, $timestamp, $merchant->private_key);
        $headers = [
            'Content-Type' => 'application/json',
            'X-TIMESTAMP' => $timestamp,
            'X-SIGNATURE' => $signed['signature'],
            'X-PARTNER-ID' => $merchant->partner_id,
            'X-EXTERNAL-ID' => $this->numericId(29),
            'CHANNEL-ID' => $merchant->channel_id,
        ];
        $url = rtrim($merchant->base_url, '/').self::QUERY_ENDPOINT;
        $response = Http::connectTimeout(5)->timeout(30)->withHeaders($headers)
            ->withBody($bodyJson, 'application/json')
            ->post($url);
        $responseBody = $response->json() ?: [];
        $actualCode = (string) ($responseBody['responseCode'] ?? $response->status());
        $actualMessage = (string) ($responseBody['responseMessage'] ?? '');
        $passed = $this->matchesExpectedResponse($case, $actualCode, $actualMessage);

        return [
            'test_no' => $case['no'],
            'no' => $case['no'],
            'service' => $case['service'],
            'execution_type' => $case['execution_type'],
            'scenario' => $case['scenario'],
            'expected_code' => $case['expected_code'],
            'expected_message' => $case['expected_message'] ?? '',
            'request' => ['url' => $url, 'method' => 'POST', 'headers' => $headers, 'body' => $body],
            'response' => ['http_status' => $response->status(), 'body' => $responseBody ?: $response->body()],
            'actual_code' => $actualCode,
            'actual_message' => $actualMessage,
            'passed' => $passed,
            'result' => $passed ? 'PASS' : 'FAIL',
            'notes' => $case['notes'],
            'metadata' => [],
        ];
    }

    private function nonAutomatedResult(array $case): array
    {
        $result = match ($case['execution_type']) {
            'manual' => 'MANUAL',
            'requires_payment' => 'WAITING',
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

    private function numericId(int $length): string
    {
        $seed = now('Asia/Jakarta')->format('YmdHisv').random_int(100000000, 999999999);
        $digits = preg_replace('/\\D/', '', $seed);

        if (strlen($digits) >= $length) {
            return substr($digits, 0, $length);
        }

        return str_pad($digits, $length, (string) random_int(0, 9));
    }

    private function nonexistentReferenceNo(): string
    {
        $referenceNo = now('Asia/Jakarta')->format('YmdHis').random_int(10, 99);

        return substr($referenceNo, 0, 16);
    }

    private function matchesExpectedResponse(array $case, string $actualCode, string $actualMessage): bool
    {
        $codeMatches = $actualCode === (string) $case['expected_code'];
        $expectedMessage = $case['expected_message'] ?? null;
        $messageMatches = blank($expectedMessage)
            || $this->normalizeResponseMessage($actualMessage) === $this->normalizeResponseMessage($expectedMessage);

        return $codeMatches && $messageMatches;
    }

    private function normalizeResponseMessage(?string $message): string
    {
        return Str::of($message ?? '')
            ->squish()
            ->lower()
            ->toString();
    }
}
