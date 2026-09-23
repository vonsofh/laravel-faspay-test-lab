<?php

namespace Vonso\FaspayTestLab\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Vonso\FaspayTestLab\Models\FaspayCallback;

class RecordFaspayCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        $callback = FaspayCallback::create($this->context($request));
        $request->attributes->set('faspay-test-lab.callback', $callback);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            $callback->update([
                'http_status' => $this->exceptionStatus($exception),
                'signature_status' => $callback->signature_status ?? 'not_checked',
                'error' => mb_substr($exception::class.': '.$exception->getMessage(), 0, 2000),
            ]);

            throw $exception;
        }

        $body = $this->responseBody($response);
        $callback->update([
            'http_status' => $response->getStatusCode(),
            'response_code' => is_array($body) ? ($body['responseCode'] ?? $body['response_code'] ?? null) : null,
            'response_body' => is_array($body) ? $body : ['raw' => mb_substr((string) $response->getContent(), 0, 10000)],
        ]);

        return $response;
    }

    /** @return array<string, mixed> */
    private function context(Request $request): array
    {
        $body = $this->requestBody($request);
        $storedBody = $body;
        unset($storedBody['signature']);
        $service = match ($request->route()?->getName()) {
            'faspay-test-lab.qris.notification' => 'qris',
            'faspay-test-lab.va.inquiry' => 'va_inquiry',
            'faspay-test-lab.va.payment' => 'va_payment',
            'faspay-test-lab.direct-debit.notification' => 'direct_debit',
            default => 'unknown',
        };

        return [
            'service' => $service,
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'external_id' => $request->header('X-EXTERNAL-ID'),
            'reference_no' => $body['originalReferenceNo']
                ?? $body['paymentRequestId']
                ?? $body['inquiryRequestId']
                ?? $body['trx_id']
                ?? null,
            'signature_status' => 'not_checked',
            'http_status' => 0,
            'request_headers' => $this->headers($request),
            'request_body' => $storedBody,
            'client_ip' => $request->ip(),
            'content_type' => $request->header('Content-Type'),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
        ];
    }

    private function responseBody(Response $response): ?array
    {
        $body = json_decode((string) $response->getContent(), true);

        return is_array($body) ? $body : null;
    }

    private function exceptionStatus(Throwable $exception): int
    {
        return method_exists($exception, 'getStatusCode')
            ? (int) $exception->getStatusCode()
            : 500;
    }

    private function headers(Request $request): array
    {
        return collect([
            'Content-Type', 'X-TIMESTAMP', 'X-PARTNER-ID', 'X-EXTERNAL-ID', 'CHANNEL-ID',
        ])->mapWithKeys(fn (string $header): array => [$header => $request->header($header)])->all();
    }

    /** @return array<string, mixed> */
    private function requestBody(Request $request): array
    {
        if ($request->isJson()) {
            return $request->json()->all();
        }

        if (! str_contains(strtolower((string) $request->header('Content-Type')), 'xml')) {
            return $request->all();
        }

        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string($request->getContent(), 'SimpleXMLElement', LIBXML_NONET | LIBXML_NOCDATA);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        if ($xml === false) {
            return ['raw' => mb_substr($request->getContent(), 0, 10000)];
        }

        $body = [];
        foreach ($xml->children() as $key => $value) {
            $body[$key] = (string) $value;
        }

        return $body;
    }
}
