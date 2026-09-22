<?php

namespace Vonso\FaspayTestLab\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Vonso\FaspayTestLab\Models\FaspayCallback;

class DirectDebitNotificationService
{
    public function handle(Request $request): JsonResponse|Response
    {
        $xml = str_contains(strtolower((string) $request->header('Content-Type')), 'xml');
        $body = $xml ? $this->xmlBody($request->getContent()) : $request->all();

        if (! config('faspay-test-lab.direct_debit_notification.enabled', true)) {
            return $this->respond($request, $body, '01', 'Notification disabled', 404, $xml);
        }

        foreach (['trx_id', 'merchant_id', 'bill_no', 'payment_status_code', 'signature'] as $field) {
            if (blank($body[$field] ?? null)) {
                return $this->respond($request, $body, '01', "Missing {$field}", 400, $xml);
            }
        }

        $userId = (string) config('faspay-test-lab.direct_debit_notification.user_id');
        $password = (string) config('faspay-test-lab.direct_debit_notification.password');
        $merchantId = (string) config('faspay-test-lab.direct_debit_notification.merchant_id');
        if ($userId === '' || $password === '' || $merchantId === '') {
            return $this->respond($request, $body, '01', 'Credentials not configured', 503, $xml);
        }
        if (! hash_equals($merchantId, (string) $body['merchant_id'])) {
            return $this->respond($request, $body, '01', 'Invalid merchant', 400, $xml);
        }

        $expected = sha1(md5($userId.$password.(string) $body['bill_no'].(string) $body['payment_status_code']));
        if (! hash_equals($expected, strtolower((string) $body['signature']))) {
            return $this->respond($request, $body, '01', 'Invalid signature', 401, $xml, 'invalid');
        }

        return $this->respond($request, $body, '00', 'Success', 200, $xml, 'valid');
    }

    private function respond(
        Request $request,
        array $requestBody,
        string $code,
        string $description,
        int $status,
        bool $xml,
        string $signatureStatus = 'not_checked',
    ): JsonResponse|Response {
        $body = [
            'response' => 'Payment Notification',
            'trx_id' => (string) ($requestBody['trx_id'] ?? ''),
            'merchant_id' => (string) ($requestBody['merchant_id'] ?? ''),
            'bill_no' => (string) ($requestBody['bill_no'] ?? ''),
            'response_code' => $code,
            'response_desc' => $description,
            'response_date' => now()->format('Y-m-d H:i:s'),
        ];

        FaspayCallback::create([
            'service' => 'direct_debit',
            'reference_no' => $requestBody['trx_id'] ?? null,
            'signature_status' => $signatureStatus,
            'http_status' => $status,
            'response_code' => $code,
            'request_headers' => ['Content-Type' => $request->header('Content-Type')],
            'request_body' => $requestBody,
            'response_body' => $body,
            'client_ip' => $request->ip(),
        ]);

        if (! $xml) {
            return response()->json($body, $status, [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        $nodes = collect($body)->map(
            static fn (mixed $value, string $key): string => sprintf(
                '<%1$s>%2$s</%1$s>',
                $key,
                htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            ),
        )->implode('');

        return response('<?xml version="1.0" encoding="utf-8"?><faspay>'.$nodes.'</faspay>', $status)
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    private function xmlBody(string $raw): array
    {
        if ($raw === '') {
            return [];
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($raw, 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($xml === false) {
            return [];
        }

        $body = [];
        foreach ($xml->children() as $key => $value) {
            $body[$key] = (string) $value;
        }

        return $body;
    }
}
