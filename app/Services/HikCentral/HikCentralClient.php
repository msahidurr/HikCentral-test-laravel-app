<?php

namespace App\Services\HikCentral;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class HikCentralClient
{
    public function post(string $uri, array $payload = [])
    {
        $method = 'POST';
        $accept = 'application/json';
        $contentType = 'application/json;charset=UTF-8';

        $body = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($body === false) {
            throw new RuntimeException('JSON encoding failed.');
        }

        $key = config('hikcentral.app_key');
        $secret = config('hikcentral.app_secret');

        $nonce = (string) Str::uuid();
        $timestamp = (string) round(microtime(true) * 1000);

        $signedHeaders = [
            'x-ca-key' => $key,
            'x-ca-nonce' => $nonce,
            'x-ca-timestamp' => $timestamp,
        ];

        ksort($signedHeaders);

        $signatureHeaders = implode(
            ',',
            array_keys($signedHeaders)
        );

        $canonicalHeaders = '';

        foreach ($signedHeaders as $name => $value) {
            $canonicalHeaders .= $name . ':' . $value . "\n";
        }

        $stringToSign =
            $method . "\n" .
            $accept . "\n" .
            "\n" .
            $contentType . "\n" .
            "\n" .
            $canonicalHeaders .
            $uri;

        $signature = base64_encode(
            hash_hmac(
                'sha256',
                $stringToSign,
                $secret,
                true
            )
        );

        $headers = [
            'Accept' => $accept,
            'Content-Type' => $contentType,
            'x-ca-key' => $key,
            'x-ca-nonce' => $nonce,
            'x-ca-timestamp' => $timestamp,
            'x-ca-signature-headers' => $signatureHeaders,
            'x-ca-signature' => $signature,
        ];

        if (config('hikcentral.user_id')) {
            $headers['userId'] = config('hikcentral.user_id');
        }

        $baseUrl = rtrim(
            config('hikcentral.base_url'),
            '/'
        );

        $response = Http::withHeaders($headers)
            ->withBody($body, $contentType)
            ->timeout(60)
            ->post($baseUrl . $uri);

        if (!$response->successful()) {
            throw new RuntimeException(
                'HikCentral HTTP error: ' .
                $response->status() . ' ' .
                $response->body()
            );
        }

        $result = $response->json();

        if (!is_array($result)) {
            throw new RuntimeException(
                'Invalid HikCentral JSON response.'
            );
        }

        if ((string) ($result['code'] ?? '') !== '0') {
            throw new RuntimeException(
                'HikCentral API error: ' .
                ($result['msg'] ?? 'Unknown error')
            );
        }

        return $result;
    }
}
