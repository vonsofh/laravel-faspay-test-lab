<?php

namespace Vonso\FaspayTestLab\Services;

use RuntimeException;

class FaspaySnapSigner
{
    public function sign(
        string $method,
        string $endpoint,
        string $bodyJson,
        string $timestamp,
        string $privateKeyPem,
    ): array {
        $bodyHash = strtolower(hash('sha256', $bodyJson));
        $stringToSign = strtoupper($method).':'.$endpoint.':'.$bodyHash.':'.$timestamp;

        $privateKeyPem = str_replace('\\n', "\n", trim($privateKeyPem));
        $privateKey = openssl_pkey_get_private($privateKeyPem);

        if (! $privateKey) {
            throw new RuntimeException('Kunci privat merchant tidak dapat dibaca oleh OpenSSL.');
        }

        $signature = '';
        $ok = openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        if (! $ok) {
            throw new RuntimeException('Gagal membuat SHA256withRSA signature.');
        }

        return [
            'body_hash' => $bodyHash,
            'string_to_sign' => $stringToSign,
            'signature' => base64_encode($signature),
        ];
    }
}
