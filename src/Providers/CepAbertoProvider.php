<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Providers;

use GuzzleHttp\Promise\PromiseInterface;
use PauloHortelan\LaraCep\Exceptions\ProviderException;
use Throwable;

final class CepAbertoProvider extends AbstractProvider
{
    public function identifier(): string
    {
        return 'cep_aberto';
    }

    public function requestAsync(string $cep): PromiseInterface
    {
        $token = (string) ($this->config['token'] ?? '');

        if ($token === '') {
            throw new ProviderException($this->identifier(), 'CEP Aberto token not configured.');
        }

        return $this->client
            ->requestAsync('GET', "https://www.cepaberto.com/api/v3/cep?cep={$cep}", [
                'headers' => [
                    'Authorization' => "Token token={$token}",
                    'Content-Type' => 'application/json;charset=utf-8',
                ],
            ])
            ->then(function ($response): array {
                $payload = $this->parseJson($response);

                if (empty($payload)) {
                    throw new ProviderException($this->identifier(), 'CEP not found in CEP Aberto.');
                }

                return [
                    'zipCode' => $this->normalizeZipCode((string) ($payload['cep'] ?? '')),
                    'state' => (string) ($payload['estado'] ?? ''),
                    'city' => (string) ($payload['cidade'] ?? ''),
                    'district' => (string) ($payload['bairro'] ?? ''),
                    'street' => (string) ($payload['logradouro'] ?? ''),
                    'provider' => $this->identifier(),
                ];
            })
            ->otherwise(function (Throwable $exception): never {
                throw $this->toProviderException($exception, 'Could not connect to CEP Aberto.');
            });
    }
}
