<?php

declare(strict_types=1);

use PauloHortelan\LaraCep\Exceptions\CepLookupException;
use PauloHortelan\LaraCep\Exceptions\InvalidCepException;
use PauloHortelan\LaraCep\Facades\LaraCep;
use PauloHortelan\LaraCep\Tests\Support\FakeEmptyProvider;
use PauloHortelan\LaraCep\Tests\Support\FakeFailingProvider;
use PauloHortelan\LaraCep\Tests\Support\FakeSuccessfulProvider;

beforeEach(function (): void {
    FakeEmptyProvider::$calls = 0;
    FakeSuccessfulProvider::$calls = 0;
    FakeFailingProvider::$calls = 0;
});

it('normalizes cep input and returns address data', function (): void {
    config()->set('lara-cep', [
        'async' => false,
        'cache' => ['enabled' => false],
        'providers' => [
            [
                'enabled' => true,
                'class' => FakeSuccessfulProvider::class,
                'identifier' => 'provider_one',
            ],
        ],
    ]);

    $address = LaraCep::find('01001-000');

    expect($address->zipCode)->toBe('01001000')
        ->and($address->provider)->toBe('provider_one');
});

it('uses cache when enabled', function (): void {
    config()->set('lara-cep', [
        'async' => false,
        'cache' => [
            'enabled' => true,
            'ttl' => 600,
            'prefix' => 'test',
        ],
        'providers' => [
            [
                'enabled' => true,
                'class' => FakeSuccessfulProvider::class,
                'identifier' => 'cached_provider',
            ],
        ],
    ]);

    LaraCep::find('12345678');
    LaraCep::find('12345678');

    expect(FakeSuccessfulProvider::$calls)->toBe(1);
});

it('runs only until first success in sequential mode', function (): void {
    config()->set('lara-cep', [
        'async' => false,
        'cache' => ['enabled' => false],
        'providers' => [
            [
                'enabled' => true,
                'class' => FakeSuccessfulProvider::class,
                'identifier' => 'first_success',
            ],
            [
                'enabled' => true,
                'class' => FakeSuccessfulProvider::class,
                'identifier' => 'second_success',
            ],
        ],
    ]);

    $address = LaraCep::find('99999999');

    expect($address->provider)->toBe('first_success')
        ->and(FakeSuccessfulProvider::$calls)->toBe(1);
});

it('hits all enabled providers in async mode', function (): void {
    config()->set('lara-cep', [
        'async' => true,
        'cache' => ['enabled' => false],
        'providers' => [
            [
                'enabled' => true,
                'class' => FakeSuccessfulProvider::class,
                'identifier' => 'first_success',
            ],
            [
                'enabled' => true,
                'class' => FakeSuccessfulProvider::class,
                'identifier' => 'second_success',
            ],
        ],
    ]);

    $address = LaraCep::find('99999999');

    expect($address->provider)->toBeIn(['first_success', 'second_success'])
        ->and(FakeSuccessfulProvider::$calls)->toBe(2);
});

it('falls back to next provider when one fails in sequential mode', function (): void {
    config()->set('lara-cep', [
        'async' => false,
        'cache' => ['enabled' => false],
        'providers' => [
            [
                'enabled' => true,
                'class' => FakeFailingProvider::class,
                'identifier' => 'first_failing',
            ],
            [
                'enabled' => true,
                'class' => FakeSuccessfulProvider::class,
                'identifier' => 'second_success',
            ],
        ],
    ]);

    $address = LaraCep::find('54321000');

    expect($address->provider)->toBe('second_success')
        ->and(FakeFailingProvider::$calls)->toBe(1)
        ->and(FakeSuccessfulProvider::$calls)->toBe(1);
});

it('throws lookup exception when all providers fail', function (): void {
    config()->set('lara-cep', [
        'async' => true,
        'cache' => ['enabled' => false],
        'providers' => [
            [
                'enabled' => true,
                'class' => FakeFailingProvider::class,
                'identifier' => 'fail_one',
            ],
            [
                'enabled' => true,
                'class' => FakeFailingProvider::class,
                'identifier' => 'fail_two',
            ],
        ],
    ]);

    expect(fn () => LaraCep::find('54321000'))
        ->toThrow(CepLookupException::class, 'All CEP providers returned an error.');
});

it('falls back when provider returns empty payload in sequential mode', function (): void {
    config()->set('lara-cep', [
        'async' => false,
        'cache' => ['enabled' => false],
        'providers' => [
            [
                'enabled' => true,
                'class' => FakeEmptyProvider::class,
                'identifier' => 'empty_provider',
            ],
            [
                'enabled' => true,
                'class' => FakeSuccessfulProvider::class,
                'identifier' => 'success_provider',
            ],
        ],
    ]);

    $address = LaraCep::find('54321000');

    expect($address->provider)->toBe('success_provider')
        ->and(FakeEmptyProvider::$calls)->toBe(1)
        ->and(FakeSuccessfulProvider::$calls)->toBe(1);
});

it('throws lookup exception when all providers return empty payloads', function (): void {
    config()->set('lara-cep', [
        'async' => true,
        'cache' => ['enabled' => false],
        'providers' => [
            [
                'enabled' => true,
                'class' => FakeEmptyProvider::class,
                'identifier' => 'empty_one',
            ],
            [
                'enabled' => true,
                'class' => FakeEmptyProvider::class,
                'identifier' => 'empty_two',
            ],
        ],
    ]);

    expect(fn () => LaraCep::find('54321000'))
        ->toThrow(CepLookupException::class, 'All CEP providers returned an error.');
});

it('throws invalid cep exception for invalid input', function (): void {
    config()->set('lara-cep', [
        'async' => false,
        'cache' => ['enabled' => false],
        'providers' => [
            [
                'enabled' => true,
                'class' => FakeSuccessfulProvider::class,
                'identifier' => 'provider_one',
            ],
        ],
    ]);

    expect(fn () => LaraCep::find('123456789'))
        ->toThrow(InvalidCepException::class);
});
