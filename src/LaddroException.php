<?php

namespace Laddro\Career;

class LaddroException extends \Exception
{
    private int $status;
    private ?string $errorCode;

    public function __construct(string $message, int $status, ?string $code = null)
    {
        parent::__construct($message, $status);
        $this->status = $status;
        $this->errorCode = $code;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getErrorCode(): ?string
    {
        return $this->errorCode;
    }

    public function isAuthError(): bool
    {
        return $this->status === 401;
    }

    public function isUsageLimitError(): bool
    {
        return $this->status === 402;
    }

    public function isNotFound(): bool
    {
        return $this->status === 404;
    }
}
