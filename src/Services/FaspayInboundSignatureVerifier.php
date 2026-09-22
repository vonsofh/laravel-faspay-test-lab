<?php

namespace Vonso\FaspayTestLab\Services;

use Illuminate\Http\Request;
use OpenSSLAsymmetricKey;
use Throwable;

class FaspayInboundSignatureVerifier
{
    /**
     * @param  array<int, string>  $endpointCandidates
     */
    public function verify(Request $request, array $endpointCandidates, string $configKey): bool
    {
        $publicKey = $this->publicKey($configKey);
        $signature = base64_decode(trim((string) $request->header('X-SIGNATURE')), true);
        if (! $publicKey instanceof OpenSSLAsymmetricKey || $signature === false || $signature === '') {
            return false;
        }

        $rawBody = $request->getContent();
        $hashes = [hash('sha256', $rawBody)];
        $decoded = json_decode($rawBody, true);
        if (is_array($decoded)) {
            $minified = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if (is_string($minified) && $minified !== $rawBody) {
                $hashes[] = hash('sha256', $minified);
            }
        }

        foreach (array_unique($hashes) as $hash) {
            foreach (array_unique($endpointCandidates) as $endpoint) {
                $stringToSign = strtoupper($request->method()).':'.$endpoint.':'.$hash.':'.$request->header('X-TIMESTAMP');

                try {
                    if (openssl_verify($stringToSign, $signature, $publicKey, OPENSSL_ALGO_SHA256) === 1) {
                        return true;
                    }
                } catch (Throwable) {
                    return false;
                }
            }
        }

        return false;
    }

    private function publicKey(string $configKey): OpenSSLAsymmetricKey|false|null
    {
        $configured = config("{$configKey}.faspay_public_key");
        if (is_string($configured) && trim($configured) !== '') {
            return openssl_pkey_get_public(str_replace('\\n', "\n", $configured));
        }

        $path = config("{$configKey}.faspay_public_key_path");
        if (! is_string($path) || trim($path) === '') {
            return null;
        }

        $value = @file_get_contents($path);

        return is_string($value) ? openssl_pkey_get_public($value) : null;
    }
}
