<?php

namespace Lakipay\SDK;

final class PaymentsClient
{
    private Config $config;
    private HttpClient $http;

    public function __construct(Config $config, HttpClient $http)
    {
        $this->config = $config;
        $this->http = $http;
    }

    /**
     * @return array<string,mixed>
     * @throws LakipayException
     */
    private function unwrapResponse(array $body): array
    {
        $status = $body['status'] ?? null;
        $message = $body['message'] ?? '';

        if ($status === 'ERROR') {
            $code = $body['error_code'] ?? null;
            $details = $body['errors'] ?? [];
            if (!is_array($details)) {
                $details = [];
            }
            throw new LakipayException(
                $message !== '' ? $message : 'Lakipay API returned logical error',
                200,
                is_string($code) ? $code : null,
                $details
            );
        }

        $data = $body['data'] ?? [];
        if (!is_array($data)) {
            $data = [];
        }
        return $data;
    }

    /**
     * @return array<string,string>
     */
    private function authHeaders(): array
    {
        return [
            'X-API-Key' => $this->config->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    /**
     * @param float $amount
     * @param string $currency
     * @param string $phoneNumber
     * @param string $medium
     * @param string $reference
     * @param string|null $description
     * @param string|null $callbackUrl
     * @param array<string,string|null>|null $redirects ['success' => ..., 'failed' => ...]
     * @param bool|null $merchantPaysFee
     * @return array<string,mixed>
     * @throws LakipayException
     */
    public function createDirectPayment(
        float $amount,
        string $currency,
        string $phoneNumber,
        string $medium,
        string $reference,
        ?string $description = null,
        ?string $callbackUrl = null,
        ?array $redirects = null,
        ?bool $merchantPaysFee = null
    ): array {
        $url = $this->config->resolvedBaseUrl() . '/api/v2/payment/direct';

        $body = [
            'amount' => $amount,
            'currency' => $currency,
            'phone_number' => $phoneNumber,
            'medium' => $medium,
            'reference' => $reference,
        ];

        if ($description !== null) {
            $body['description'] = $description;
        }
        if ($callbackUrl !== null) {
            $body['callback_url'] = $callbackUrl;
        }
        if ($redirects !== null) {
            $redirectBody = [];
            if (isset($redirects['success'])) {
                $redirectBody['success_url'] = $redirects['success'];
            }
            if (isset($redirects['failed'])) {
                $redirectBody['failure_url'] = $redirects['failed'];
            }
            $body['redirects'] = $redirectBody;
        }
        if ($merchantPaysFee !== null) {
            $body['merchant_pays_fee'] = $merchantPaysFee;
        }

        $raw = $this->http->request('POST', $url, $this->authHeaders(), $body);
        return $this->unwrapResponse($raw);
    }

    /**
     * @param float $amount
     * @param string $currency
     * @param string $phoneNumber
     * @param string $medium
     * @param string $reference
     * @param string|null $callbackUrl
     * @return array<string,mixed>
     * @throws LakipayException
     */
    public function createWithdrawal(
        float $amount,
        string $currency,
        string $phoneNumber,
        string $medium,
        string $reference,
        ?string $callbackUrl = null
    ): array {
        $url = $this->config->resolvedBaseUrl() . '/api/v2/payment/withdrawal';

        $body = [
            'amount' => $amount,
            'currency' => $currency,
            'phone_number' => $phoneNumber,
            'medium' => $medium,
            'reference' => $reference,
        ];

        if ($callbackUrl !== null) {
            $body['callback_url'] = $callbackUrl;
        }

        $raw = $this->http->request('POST', $url, $this->authHeaders(), $body);
        return $this->unwrapResponse($raw);
    }

    /**
     * @param float $amount
     * @param string $currency
     * @param string $phoneNumber
     * @param string $reference
     * @param array<string,string> $redirects ['success' => ..., 'failed' => ...]
     * @param array<int,string>|null $supportedMediums
     * @param string|null $description
     * @param string|null $callbackUrl
     * @return array<string,mixed>
     * @throws LakipayException
     */
    public function createHostedCheckout(
        float $amount,
        string $currency,
        string $phoneNumber,
        string $reference,
        array $redirects,
        ?array $supportedMediums = null,
        ?string $description = null,
        ?string $callbackUrl = null
    ): array {
        $url = $this->config->resolvedBaseUrl() . '/api/v2/payment/checkout';

        $body = [
            'amount' => $amount,
            'currency' => $currency,
            'phone_number' => $phoneNumber,
            'reference' => $reference,
            'redirects' => [
                'success_url' => $redirects['success'] ?? null,
                'failure_url' => $redirects['failed'] ?? null,
            ],
        ];

        if ($supportedMediums !== null) {
            $body['supported_mediums'] = $supportedMediums;
        }
        if ($description !== null) {
            $body['description'] = $description;
        }
        if ($callbackUrl !== null) {
            $body['callback_url'] = $callbackUrl;
        }

        $raw = $this->http->request('POST', $url, $this->authHeaders(), $body);
        return $this->unwrapResponse($raw);
    }

    /**
     * @param string $transactionId
     * @return array<string,mixed>
     * @throws LakipayException
     */
    public function getTransaction(string $transactionId): array
    {
        $url = $this->config->resolvedBaseUrl() . '/api/v2/payment/transaction/' . urlencode($transactionId);
        $raw = $this->http->request('GET', $url, $this->authHeaders(), null);
        return $this->unwrapResponse($raw);
    }
}

