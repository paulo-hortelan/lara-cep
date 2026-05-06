<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Providers;

use GuzzleHttp\Promise\PromiseInterface;
use PauloHortelan\LaraCep\Exceptions\ProviderException;
use Throwable;

final class BrasilApiProvider extends AbstractProvider
{
    public function identifier(): string
    {
        return 'brasil_api';
    }

    public function requestAsync(string $cep): PromiseInterface
    {
        return $this->client
            ->requestAsync('GET', "https://brasilapi.com.br/api/cep/v1/{$cep}")
            ->then(function ($response): array {
                $payload = $this->parseJson($response);

                if (empty($payload) || isset($payload['errors'])) {
                    throw new ProviderException($this->identifier(), 'CEP not found in BrasilAPI.');
                }

                return [
                    'zipCode' => $this->normalizeZipCode((string) ($payload['cep'] ?? '')),
                    'state' => (string) ($payload['state'] ?? ''),
                    'city' => (string) ($payload['city'] ?? ''),
                    'district' => (string) ($payload['neighborhood'] ?? ''),
                    'street' => (string) ($payload['street'] ?? ''),
                    'provider' => $this->identifier(),
                ];
            })
            ->otherwise(function (Throwable $exception): never {
                throw $this->toProviderException($exception, 'Could not connect to BrasilAPI.');
            });
    }
}
