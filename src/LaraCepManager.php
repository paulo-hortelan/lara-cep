<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Promise\AggregateException;
use GuzzleHttp\Promise\Utils;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use PauloHortelan\LaraCep\Contracts\ProviderInterface;
use PauloHortelan\LaraCep\Exceptions\CepLookupException;
use PauloHortelan\LaraCep\Exceptions\InvalidCepException;
use PauloHortelan\LaraCep\Exceptions\ProviderException;
use Throwable;

final class LaraCepManager
{
    private const CEP_SIZE = 8;

    /**
     * @var array<string, mixed>
     */
    private readonly array $config;

    public function __construct(
        private readonly CacheFactory $cacheFactory,
        array $config,
        private readonly ?ClientInterface $httpClient = null,
    ) {
        $this->config = $config;
    }

    public function find(string|int $cep): Address
    {
        $normalizedCep = $this->normalizeCep($cep);

        if (! $this->isCacheEnabled()) {
            return $this->resolve($normalizedCep);
        }

        $cacheTtl = max(1, (int) data_get($this->config, 'cache.ttl', 3600));

        /** @var array<string, mixed> $cached */
        $cached = $this->cacheRepository()->remember(
            $this->cacheKey($normalizedCep),
            $cacheTtl,
            fn (): array => $this->resolve($normalizedCep)->toArray(),
        );

        return Address::fromArray($cached);
    }

    public function lookup(string|int $cep): Address
    {
        return $this->find($cep);
    }

    private function normalizeCep(string|int $cep): string
    {
        $sanitized = preg_replace('/\D+/', '', (string) $cep) ?? '';

        if ($sanitized === '' || strlen($sanitized) > self::CEP_SIZE) {
            throw new InvalidCepException('CEP must contain up to 8 digits.');
        }

        return str_pad($sanitized, self::CEP_SIZE, '0', STR_PAD_LEFT);
    }

    private function resolve(string $cep): Address
    {
        $providers = $this->buildProviders();

        if ($providers === []) {
            throw new CepLookupException('No CEP providers are enabled.', [], 1);
        }

        return $this->isAsyncEnabled()
            ? $this->resolveAsync($cep, $providers)
            : $this->resolveSequential($cep, $providers);
    }

    /**
     * @param  array<int, ProviderInterface>  $providers
     */
    private function resolveAsync(string $cep, array $providers): Address
    {
        $promises = [];

        foreach ($providers as $provider) {
            $promises[$provider->identifier()] = $provider->requestAsync($cep);
        }

        try {
            /** @var array<string, mixed> $result */
            $result = Utils::any($promises)->wait();

            return Address::fromArray($result);
        } catch (AggregateException $exception) {
            $errors = [];

            foreach ($exception->getReason() as $reason) {
                $errors[] = $this->formatProviderError($reason);
            }

            throw new CepLookupException('All CEP providers returned an error.', $errors, 2);
        } catch (Throwable $exception) {
            throw new CepLookupException(
                $exception->getMessage() !== '' ? $exception->getMessage() : 'Could not resolve CEP.',
                [],
                2,
            );
        }
    }

    /**
     * @param  array<int, ProviderInterface>  $providers
     */
    private function resolveSequential(string $cep, array $providers): Address
    {
        $errors = [];

        foreach ($providers as $provider) {
            try {
                /** @var array<string, mixed> $result */
                $result = $provider->requestAsync($cep)->wait();

                return Address::fromArray($result);
            } catch (Throwable $exception) {
                $errors[] = $this->formatProviderError($exception, $provider->identifier());
            }
        }

        throw new CepLookupException('All CEP providers returned an error.', $errors, 2);
    }

    /**
     * @return array<int, ProviderInterface>
     */
    private function buildProviders(): array
    {
        $providersConfig = data_get($this->config, 'providers', []);

        if (! is_array($providersConfig)) {
            throw new InvalidArgumentException('Invalid providers configuration.');
        }

        $providers = [];

        foreach ($providersConfig as $providerConfig) {
            if (! is_array($providerConfig) || ! ($providerConfig['enabled'] ?? false)) {
                continue;
            }

            $providerClass = $providerConfig['class'] ?? null;

            if (! is_string($providerClass) || ! is_subclass_of($providerClass, ProviderInterface::class)) {
                continue;
            }

            $providers[] = new $providerClass($this->httpClient(), $providerConfig);
        }

        return $providers;
    }

    private function httpClient(): ClientInterface
    {
        if ($this->httpClient instanceof ClientInterface) {
            return $this->httpClient;
        }

        return new Client([
            'timeout' => (float) data_get($this->config, 'http.timeout', 8),
            'connect_timeout' => (float) data_get($this->config, 'http.connect_timeout', 5),
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json;charset=utf-8',
            ],
        ]);
    }

    private function isCacheEnabled(): bool
    {
        return (bool) data_get($this->config, 'cache.enabled', true);
    }

    private function isAsyncEnabled(): bool
    {
        return (bool) data_get($this->config, 'async', true);
    }

    private function cacheKey(string $cep): string
    {
        $prefix = (string) data_get($this->config, 'cache.prefix', 'lara_cep');

        return sprintf('%s:%s', $prefix, $cep);
    }

    private function cacheRepository(): CacheRepository
    {
        $store = data_get($this->config, 'cache.store');

        if (is_string($store) && $store !== '') {
            return $this->cacheFactory->store($store);
        }

        return $this->cacheFactory->store();
    }

    /**
     * @return array{provider: string, message: string}
     */
    private function formatProviderError(mixed $reason, ?string $defaultProvider = null): array
    {
        if ($reason instanceof ProviderException) {
            return $reason->toArray();
        }

        if ($reason instanceof Throwable) {
            return [
                'provider' => $defaultProvider ?? 'unknown',
                'message' => $reason->getMessage() !== '' ? $reason->getMessage() : 'Unknown provider error.',
            ];
        }

        return [
            'provider' => $defaultProvider ?? 'unknown',
            'message' => is_string($reason) && $reason !== '' ? $reason : 'Unknown provider error.',
        ];
    }
}
