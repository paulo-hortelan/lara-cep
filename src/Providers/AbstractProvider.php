<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Providers;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use JsonException;
use PauloHortelan\LaraCep\Contracts\ProviderInterface;
use PauloHortelan\LaraCep\Exceptions\ProviderException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

abstract class AbstractProvider implements ProviderInterface
{
    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        protected readonly ClientInterface $client,
        protected readonly array $config = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function parseJson(ResponseInterface $response): array
    {
        $content = (string) $response->getBody();

        if ($content === '') {
            return [];
        }

        try {
            /** @var array<string, mixed> $decoded */
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return $decoded;
        } catch (JsonException) {
            return [];
        }
    }

    protected function toProviderException(Throwable $exception, string $fallbackMessage): ProviderException
    {
        if ($exception instanceof ProviderException) {
            return $exception;
        }

        if ($exception instanceof RequestException) {
            $response = $exception->getResponse();
            if ($response !== null) {
                $payload = $this->parseJson($response);
                if (isset($payload['message']) && is_string($payload['message']) && $payload['message'] !== '') {
                    return new ProviderException($this->identifier(), $payload['message']);
                }
            }

            return new ProviderException($this->identifier(), $fallbackMessage);
        }

        $message = $exception->getMessage() !== '' ? $exception->getMessage() : $fallbackMessage;

        return new ProviderException($this->identifier(), $message);
    }

    protected function normalizeZipCode(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? $value;
    }
}
