<?php

namespace Vonso\FaspayTestLab\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class LogFaspayCallbackIngress
{
    public function handle(Request $request, Closure $next): Response
    {
        $context = $this->context($request);

        Log::info('Faspay Test Lab callback received', $context);

        try {
            $response = $next($request);
        } catch (Throwable $exception) {
            Log::error('Faspay Test Lab callback failed before response', [
                ...$context,
                'exception' => $exception::class,
                'error' => mb_substr($exception->getMessage(), 0, 500),
            ]);

            throw $exception;
        }

        Log::info('Faspay Test Lab callback completed', [
            ...$context,
            'http_status' => $response->getStatusCode(),
            'response_code' => $this->responseCode($response),
        ]);

        return $response;
    }

    /** @return array<string, mixed> */
    private function context(Request $request): array
    {
        $body = $request->isJson() ? $request->json()->all() : $request->all();

        return [
            'route' => $request->route()?->getName(),
            'method' => $request->method(),
            'path' => $request->path(),
            'ip' => $request->ip(),
            'content_type' => $request->header('Content-Type'),
            'content_length' => $request->header('Content-Length'),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'partner_id' => $request->header('X-PARTNER-ID') ?: ($body['merchant_id'] ?? null),
            'external_id' => $request->header('X-EXTERNAL-ID'),
            'channel_id' => $request->header('CHANNEL-ID'),
            'signature_present' => filled($request->header('X-SIGNATURE') ?: ($body['signature'] ?? null)),
            'reference_no' => $body['originalReferenceNo']
                ?? $body['paymentRequestId']
                ?? $body['inquiryRequestId']
                ?? $body['trx_id']
                ?? null,
            'virtual_account_no' => isset($body['virtualAccountNo'])
                ? $this->masked((string) $body['virtualAccountNo'])
                : null,
        ];
    }

    private function responseCode(Response $response): ?string
    {
        $body = json_decode((string) $response->getContent(), true);
        if (! is_array($body)) {
            return null;
        }

        return $body['responseCode'] ?? $body['response_code'] ?? null;
    }

    private function masked(string $value): string
    {
        $digits = preg_replace('/\D+/', '', $value) ?? '';

        return strlen($digits) <= 8
            ? str_repeat('*', strlen($digits))
            : substr($digits, 0, 6).str_repeat('*', strlen($digits) - 10).substr($digits, -4);
    }
}
