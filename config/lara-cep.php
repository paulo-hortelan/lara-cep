<?php

declare(strict_types=1);

use PauloHortelan\LaraCep\Providers\BrasilApiProvider;
use PauloHortelan\LaraCep\Providers\CepAbertoProvider;
use PauloHortelan\LaraCep\Providers\OpenCepProvider;
use PauloHortelan\LaraCep\Providers\ViaCepProvider;

return [
    'async' => env('LARA_CEP_ASYNC', true),

    'cache' => [
        'enabled' => env('LARA_CEP_CACHE_ENABLED', true),
        'ttl' => env('LARA_CEP_CACHE_TTL', 3600),
        'store' => env('LARA_CEP_CACHE_STORE'),
        'prefix' => env('LARA_CEP_CACHE_PREFIX', 'lara_cep'),
    ],

    'http' => [
        'timeout' => env('LARA_CEP_HTTP_TIMEOUT', 8),
        'connect_timeout' => env('LARA_CEP_HTTP_CONNECT_TIMEOUT', 5),
    ],

    'providers' => [
        'via_cep' => [
            'enabled' => env('LARA_CEP_PROVIDER_VIA_CEP_ENABLED', true),
            'class' => ViaCepProvider::class,
        ],

        'open_cep' => [
            'enabled' => env('LARA_CEP_PROVIDER_OPEN_CEP_ENABLED', true),
            'class' => OpenCepProvider::class,
        ],

        'brasil_api' => [
            'enabled' => env('LARA_CEP_PROVIDER_BRASIL_API_ENABLED', true),
            'class' => BrasilApiProvider::class,
        ],

        'cep_aberto' => [
            'enabled' => env('LARA_CEP_PROVIDER_CEP_ABERTO_ENABLED', false),
            'class' => CepAbertoProvider::class,
            'token' => env('LARA_CEP_PROVIDER_CEP_ABERTO_TOKEN', ''),
        ],
    ],
];
