<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Contracts;

use GuzzleHttp\Promise\PromiseInterface;

interface ProviderInterface
{
    public function identifier(): string;

    public function requestAsync(string $cep): PromiseInterface;
}
