<?php

namespace Lakipay\SDK;

final class LakipaySDK
{
    private Config $config;
    private HttpClient $http;

    public PaymentsClient $payments;
    public WebhookClient $webhooks;

    public function __construct(
        string $apiKey,
        string $environment = 'sandbox',
        ?string $baseUrl = null,
        float $timeoutSeconds = 30.0,
        int $retries = 2,
        int $backoffMs = 300,
        bool $logRequests = false
    ) {
        $this->config = new Config(
            $apiKey,
            $environment,
            $baseUrl,
            $timeoutSeconds,
            $retries,
            $backoffMs,
            $logRequests
        );

        $this->http = new HttpClient($this->config);
        $this->payments = new PaymentsClient($this->config, $this->http);
        $this->webhooks = new WebhookClient();
    }

    public function getConfig(): Config
    {
        return $this->config;
    }
}

