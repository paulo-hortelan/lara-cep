<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Tests\Support;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use PauloHortelan\LaraCep\Contracts\ProviderInterface;

final class FakeEmptyProvider implements ProviderInterface
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
        return (string) ($this->config['identifier'] ?? 'fake_empty');
    }

    public function requestAsync(string $cep): PromiseInterface
    {
        self::$calls++;

        return new FulfilledPromise([
            'zipCode' => '',
            'state' => '',
            'city' => '',
            'district' => '',
            'street' => '',
            'provider' => $this->identifier(),
        ]);
    }
}
