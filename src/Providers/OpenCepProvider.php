<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Providers;

use GuzzleHttp\Promise\PromiseInterface;
use PauloHortelan\LaraCep\Exceptions\ProviderException;
use Throwable;

final class OpenCepProvider extends AbstractProvider
{
    public function identifier(): string
    {
        return 'open_cep';
    }

    public function requestAsync(string $cep): PromiseInterface
    {
        return $this->client
            ->requestAsync('GET', "https://opencep.com/v1/{$cep}")
            ->then(function ($response): array {
                $payload = $this->parseJson($response);

                if (($payload['erro'] ?? false) === true || empty($payload)) {
                    throw new ProviderException($this->identifier(), 'CEP not found in OpenCEP.');
                }

                return [
                    'zipCode' => $this->normalizeZipCode((string) ($payload['cep'] ?? '')),
                    'state' => (string) ($payload['uf'] ?? ''),
                    'city' => (string) ($payload['localidade'] ?? ''),
                    'district' => (string) ($payload['bairro'] ?? ''),
                    'street' => (string) ($payload['logradouro'] ?? ''),
                    'provider' => $this->identifier(),
                ];
            })
            ->otherwise(function (Throwable $exception): never {
                throw $this->toProviderException($exception, 'Could not connect to OpenCEP.');
            });
    }
}
