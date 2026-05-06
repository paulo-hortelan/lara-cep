<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Exceptions;

use RuntimeException;

final class CepLookupException extends RuntimeException
{
    /**
     * @param  array<int, array{provider: string, message: string}>  $errors
     */
    public function __construct(
        string $message,
        private readonly array $errors = [],
        int $code = 0,
    ) {
        parent::__construct($message, $code);
    }

    /**
     * @return array<int, array{provider: string, message: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * @return array{message: string, code: int, errors: array<int, array{provider: string, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'errors' => $this->errors,
        ];
    }
}
