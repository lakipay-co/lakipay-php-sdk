<?php

namespace Lakipay\SDK;

final class Config
{
    public string $apiKey;
    public string $environment;
    public ?string $baseUrl;
    public float $timeoutSeconds;
    public int $retries;
    public int $backoffMs;
    public bool $logRequests;

    public function __construct(
        string $apiKey,
        string $environment = 'sandbox',
        ?string $baseUrl = null,
        float $timeoutSeconds = 30.0,
        int $retries = 2,
        int $backoffMs = 300,
        bool $logRequests = false
    ) {
        $this->apiKey = $apiKey;
        $this->environment = $environment;
        $this->baseUrl = $baseUrl;
        $this->timeoutSeconds = $timeoutSeconds;
        $this->retries = $retries;
        $this->backoffMs = $backoffMs;
        $this->logRequests = $logRequests;
    }

    public function resolvedBaseUrl(): string
    {
        if ($this->baseUrl !== null && $this->baseUrl !== '') {
            return rtrim($this->baseUrl, '/');
        }

        // Both sandbox and production currently map to the same host.
        return 'https://api.lakipay.co';
    }
}

