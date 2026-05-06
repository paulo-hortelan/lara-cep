<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Tests\Support;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use PauloHortelan\LaraCep\Contracts\ProviderInterface;
use PauloHortelan\LaraCep\Exceptions\ProviderException;

final class FakeFailingProvider implements ProviderInterface
{
    public static int $calls = 0;

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        private readonly ClientInterface $client,
        private readonly array $config = [],
    ) {}

    public function identifier(): string
    {
        return (string) ($this->config['identifier'] ?? 'fake_fail');
    }

    public function requestAsync(string $cep): PromiseInterface
    {
        self::$calls++;

        return new RejectedPromise(new ProviderException($this->identifier(), 'Provider failure.'));
    }
}
