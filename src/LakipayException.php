<?php

namespace Lakipay\SDK;

use Exception;

class LakipayException extends Exception
{
    public ?int $status;
    /** @var mixed Code from API; keep untyped to match base Exception::$code */
    public $code;
    /** @var array<string,mixed> */
    public array $details;

    /**
     * @param string $message
     * @param int|null $status
     * @param string|null $code
     * @param array<string,mixed> $details
     */
    public function __construct(
        string $message,
        ?int $status = null,
        ?string $code = null,
        array $details = []
    ) {
        parent::__construct($message);
        $this->status = $status;
        $this->code = $code;
        $this->details = $details;
    }
}

