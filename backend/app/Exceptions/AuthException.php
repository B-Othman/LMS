<?php

namespace App\Exceptions;

use App\Enums\UserStatus;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthException extends HttpException
{
    private string $errorCode;

    public function __construct(int $statusCode, string $errorCode, string $message)
    {
        $this->errorCode = $errorCode;
        parent::__construct($statusCode, $message);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public static function invalidCredentials(): self
    {
        return new self(401, 'invalid_credentials', 'The provided credentials are incorrect.');
    }

    public static function accountNotActive(UserStatus $status): self
    {
        $message = match ($status) {
            UserStatus::Inactive => 'This account has been deactivated.',
            UserStatus::Suspended => 'This account has been suspended.',
            default => 'This account cannot log in.',
        };

        return new self(403, 'account_not_active', $message);
    }
}
