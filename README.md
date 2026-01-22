# Lakipay PHP SDK (`lakipay-php-sdk`)

PHP SDK for integrating with the **Lakipay core payment API** from your backend applications.

**Package:** [Packagist](https://packagist.org/packages/lakipay/lakipay-php-sdk) | [GitHub](https://github.com/lakipay-co/lakipay-php-sdk)

**Requirements:**
- PHP >= 8.0
- Composer >= 2.0

---

## 1. Installation

#### Install from Packagist 

```bash
composer require lakipay/lakipay-php-sdk
```

#### Install from GitHub

If you prefer to install directly from the GitHub repository:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/lakipay-co/lakipay-php-sdk"
    }
  ],
  "require": {
    "lakipay/lakipay-php-sdk": "dev-main"
  },
  "minimum-stability": "dev",
  "prefer-stable": true
}
```

Then run:

```bash
composer require lakipay/lakipay-php-sdk:dev-main
```

## 2. Initialization

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Lakipay\SDK\LakipaySDK;
use Lakipay\SDK\LakipayException;

$sdk = new LakipaySDK(
    apiKey: 'pk_xxx:sk_xxx',
    environment: 'production', 
    // baseUrl: 'https://api.lakipay.co', 
);
```

---

## 3. Usage

### 3.1 Direct Payment

```php
<?php

// ... initialization as above ...

try {
    $resp = $sdk->payments->createDirectPayment(
        amount: 20.0,
        currency: 'ETB',
        phoneNumber: '251923790730',
        medium: 'TELEBIRR',
        reference: 'PHPTEST-' . bin2hex(random_bytes(4)),
        description: 'PHP SDK test payment',
        callbackUrl: 'https://example.com/webhook',
        redirects: [
            'success' => 'https://example.com/success',
            'failed' => 'https://example.com/failed',
        ]
    );

    var_dump($resp);
} catch (LakipayException $e) {
    echo 'LakipayException: ' . $e->getMessage() . PHP_EOL;
}
```

### 3.2 Withdrawal

```php
$resp = $sdk->payments->createWithdrawal(
    amount: 1.0,
    currency: 'ETB',
    phoneNumber: '251923790730',
    medium: 'CBE',
    reference: 'PHPWD-' . bin2hex(random_bytes(4)),
    callbackUrl: 'https://example.com/webhook',
);
```

### 3.3 Hosted Checkout

```php
$resp = $sdk->payments->createHostedCheckout(
    amount: 100.0,
    currency: 'ETB',
    phoneNumber: '251923790730',
    reference: 'PHPHOST-' . bin2hex(random_bytes(4)),
    redirects: [
        'success' => 'https://example.com/success',
        'failed' => 'https://example.com/failed',
    ],
    supportedMediums: ['MPESA', 'TELEBIRR', 'CBE'],
    description: 'Hosted checkout from PHP SDK',
    callbackUrl: 'https://example.com/webhook',
);

// e.g. $resp['checkout_url']
```

### 3.4 Transaction Detail

```php
$transactionId = 'LKY_TXN_ID_FROM_PREVIOUS_CALL';

$resp = $sdk->payments->getTransaction($transactionId);
var_dump($resp);
```

Internally, all payment methods hit:

- `POST /api/v2/payment/direct`
- `POST /api/v2/payment/withdrawal`
- `POST /api/v2/payment/checkout`
- `GET  /api/v2/payment/transaction/{id}`

Authentication is via `X-API-Key` header.

The SDK unwraps the standard response envelope:

```json
{
  "status": "SUCCESS" | "ERROR",
  "message": "...",
  "data": { ... },
  "error_code": "OPTIONAL",
  "errors": { "field": ["..."] }
}
```

If `status` is `"ERROR"`, a `LakipayException` is thrown with `code` and `details`.

---

## 4. Webhooks

```php
use Lakipay\SDK\WebhookClient;

$rawBody = file_get_contents('php://input');
$publicKeyPem = getenv('LAKIPAY_WEBHOOK_PUBLIC_KEY') ?: '-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----';

$webhooks = new WebhookClient();

try {
    $payload = $webhooks->verifyAndParse($rawBody, $publicKeyPem);
    // $payload['event'], $payload['status'], etc.
} catch (\InvalidArgumentException $e) {
    http_response_code(400);
    echo 'Invalid webhook';
    exit;
}
```

Verification rules:

- All keys (except `signature`) are sorted alphabetically.
- Concatenated as `key=value` and joined with `&`.
- Verified with `openssl_verify(..., OPENSSL_ALGO_SHA256)` using the RSA public key provided by Lakipay.


