<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Exceptions;

use RuntimeException;

final class ProviderException extends RuntimeException
{
    public function __construct(
        public readonly string $provider,
        string $message,
        int $code = 0,
    ) {
        parent::__construct($message, $code);
    }

    /**
     * @return array{provider: string, message: string}
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'message' => $this->getMessage(),
        ];
    }
}
