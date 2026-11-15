<?php

namespace App\Services\HikCentral;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class HikCentralClient
{
    protected string $baseUrl;
    protected string $appKey;
    protected string $appSecret;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.hikcentral.base_url'), '/');

        $this->appKey = config('services.hikcentral.app_key');
        $this->appSecret = config('services.hikcentral.app_secret');

        if (!$this->baseUrl || !$this->appKey || !$this->appSecret) {
            throw new RuntimeException(
                'HikCentral configuration is incomplete.'
            );
        }
    }

    public function post(string $uri, array $payload = []): array
    {
        $method = 'POST';
        $accept = '*/*';
        $contentType = 'application/json;charset=UTF-8';
        $date = gmdate('D, d M Y H:i:s') . ' GMT';

        // Keep the exact same body for MD5, signing and HTTP request.
        $body = json_encode(
            $payload,
            JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        if ($body === false) {
            throw new RuntimeException('Unable to encode request JSON.');
        }

        $contentMd5 = base64_encode(md5($body, true));

        $signedHeaders = [
            'x-ca-key' => $this->appKey,
            'x-ca-nonce' => (string) Str::uuid(),
            'x-ca-timestamp' => (string) round(microtime(true) * 1000),
        ];

        ksort($signedHeaders);

        $signatureHeaderNames = implode(
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
            $contentMd5 . "\n" .
            $contentType . "\n" .
            $date . "\n" .
            $canonicalHeaders .
            $uri;

        $signature = base64_encode(
            hash_hmac(
                'sha256',
                $stringToSign,
                $this->appSecret,
                true
            )
        );

        $headers = [
            'Accept' => $accept,
            'Content-Type' => $contentType,
            'Content-MD5' => $contentMd5,
            'Date' => $date,
            'X-Ca-Key' => $this->appKey,
            'X-Ca-Nonce' => $signedHeaders['x-ca-nonce'],
            'X-Ca-Timestamp' => $signedHeaders['x-ca-timestamp'],
            'X-Ca-Signature-Headers' => $signatureHeaderNames,
            'X-Ca-Signature' => $signature,
        ];

        $response = Http::timeout(
            (int) config('services.hikcentral.timeout', 30)
        )
            ->withHeaders($headers)
            ->withBody($body, $contentType)
            ->post($this->baseUrl . $uri);

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
                'Invalid JSON response from HikCentral.'
            );
        }

        if ((string) ($result['code'] ?? '') !== '0') {
            throw new RuntimeException(
                'HikCentral API error: ' .
                ($result['msg'] ?? 'Unknown error') .
                ' Code: ' . ($result['code'] ?? 'Unknown')
            );
        }

        return $result;
    }
}
