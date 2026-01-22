<?php

namespace Lakipay\SDK;

final class WebhookClient
{
    /**
     * Build canonical string by:
     * - sorting keys alphabetically
     * - excluding 'signature'
     * - joining as key=value with '&'
     *
     * @param array<string,mixed> $payload
     */
    private function buildCanonicalString(array $payload): string
    {
        $keys = array_keys($payload);
        sort($keys);

        $parts = [];
        foreach ($keys as $key) {
            if ($key === 'signature') {
                continue;
            }
            $parts[] = $key . '=' . $payload[$key];
        }

        return implode('&', $parts);
    }

    /**
     * @param array<string,mixed> $payload
     */
    public function verifySignature(array $payload, string $publicKeyPem): bool
    {
        if (!isset($payload['signature']) || !is_string($payload['signature'])) {
            return false;
        }

        $canonical = $this->buildCanonicalString($payload);
        $signature = base64_decode($payload['signature'], true);
        if ($signature === false) {
            return false;
        }

        $ok = openssl_verify(
            $canonical,
            $signature,
            $publicKeyPem,
            OPENSSL_ALGO_SHA256
        );

        return $ok === 1;
    }

    /**
     * @return array<string,mixed>
     */
    public function parse(string $rawBody): array
    {
        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('Invalid JSON body for webhook');
        }
        /** @var array<string,mixed> $decoded */
        return $decoded;
    }

    /**
     * @return array<string,mixed>
     * @throws \InvalidArgumentException
     */
    public function verifyAndParse(string $rawBody, string $publicKeyPem): array
    {
        $payload = $this->parse($rawBody);
        if (!$this->verifySignature($payload, $publicKeyPem)) {
            throw new \InvalidArgumentException('Invalid Lakipay webhook signature');
        }
        return $payload;
    }
}

