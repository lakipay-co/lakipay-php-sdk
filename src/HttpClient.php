<?php

namespace Lakipay\SDK;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

final class HttpClient
{
    private Config $config;
    private Client $client;

    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->client = new Client([
            'http_errors' => false,
        ]);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array<string,string> $headers
     * @param array<string,mixed>|null $jsonBody
     * @return array<string,mixed>
     * @throws LakipayException
     */
    public function request(
        string $method,
        string $url,
        array $headers = [],
        ?array $jsonBody = null
    ): array {
        $attempt = 0;
        $lastException = null;

        while ($attempt <= $this->config->retries) {
            try {
                $options = [
                    'headers' => $headers,
                    'timeout' => $this->config->timeoutSeconds,
                ];

                if ($jsonBody !== null) {
                    $options['json'] = $jsonBody;
                }

                if ($this->config->logRequests) {
                    // Simple logging, safe for CLI
                    fwrite(STDOUT, "[LakipaySDK] {$method} {$url} body=" . json_encode($jsonBody) . PHP_EOL);
                }

                $response = $this->client->request($method, $url, $options);
                $statusCode = $response->getStatusCode();
                $bodyString = (string)$response->getBody();

                if ($this->config->logRequests) {
                    fwrite(STDOUT, "[LakipaySDK] <- {$statusCode} body=" . substr($bodyString, 0, 500) . PHP_EOL);
                }

                if ($statusCode < 200 || $statusCode >= 300) {
                    throw new LakipayException(
                        "HTTP error from Lakipay API: {$statusCode}",
                        $statusCode
                    );
                }

                $decoded = json_decode($bodyString, true);
                if (!is_array($decoded)) {
                    throw new LakipayException(
                        'Failed to parse JSON response from Lakipay API',
                        $statusCode
                    );
                }

                /** @var array<string,mixed> $decoded */
                return $decoded;
            } catch (GuzzleException|LakipayException $e) {
                $lastException = $e;
                if ($attempt >= $this->config->retries) {
                    break;
                }
                $backoffSeconds = ($this->config->backoffMs / 1000.0) * (2 ** $attempt);
                usleep((int)($backoffSeconds * 1_000_000));
                $attempt++;
            }
        }

        if ($lastException instanceof LakipayException) {
            throw $lastException;
        }

        if ($lastException instanceof GuzzleException) {
            throw new LakipayException(
                'Request to Lakipay API failed: ' . $lastException->getMessage()
            );
        }

        throw new LakipayException('Request to Lakipay API failed');
    }
}

