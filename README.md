# Lara CEP

[![Latest Version on Packagist](https://img.shields.io/packagist/v/paulo-hortelan/lara-cep.svg?style=flat-square)](https://packagist.org/packages/paulo-hortelan/lara-cep)
[![Tests](https://img.shields.io/github/actions/workflow/status/paulo-hortelan/lara-cep/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/paulo-hortelan/lara-cep/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Code Style](https://img.shields.io/github/actions/workflow/status/paulo-hortelan/lara-cep/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/paulo-hortelan/lara-cep/actions?query=workflow%3A%22Fix+PHP+code+style+issues%22+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/paulo-hortelan/lara-cep.svg?style=flat-square)](https://packagist.org/packages/paulo-hortelan/lara-cep)

`lara-cep` é um pacote Laravel para consultar CEPs brasileiros usando múltiplos provedores, com:

- lista de provedores configurável
- TTL de cache configurável
- modo assíncrono configurável (consulta todos os provedores habilitados em paralelo)

O pacote foi inspirado no comportamento do CEP Promise e adaptado para fluxos de pacote Laravel.

## Instalação

```bash
composer require paulo-hortelan/lara-cep
```

Publique a configuração:

```bash
php artisan vendor:publish --tag=lara-cep-config
```

## Uso Básico

```php
use PauloHortelan\LaraCep\Facades\LaraCep;

$address = LaraCep::find('01001-000');

$address->zipCode;  // 01001000
$address->state;    // SP
$address->city;     // Sao Paulo
$address->district; // Centro
$address->street;   // Praca da Se...
$address->provider; // via_cep, open_cep, brasil_api...
```

Você também pode chamar:

```php
$address = app('lara-cep')->find('01001000');
```

## Configuração

`config/lara-cep.php`

```php
return [
    'async' => true,

    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'store' => null,
        'prefix' => 'lara_cep',
    ],

    'providers' => [
        'via_cep' => [
            'enabled' => true,
            'class' => PauloHortelan\LaraCep\Providers\ViaCepProvider::class,
        ],

        'open_cep' => [
            'enabled' => true,
            'class' => PauloHortelan\LaraCep\Providers\OpenCepProvider::class,
        ],

        'brasil_api' => [
            'enabled' => true,
            'class' => PauloHortelan\LaraCep\Providers\BrasilApiProvider::class,
        ],

        'cep_aberto' => [
            'enabled' => false,
            'class' => PauloHortelan\LaraCep\Providers\CepAbertoProvider::class,
            'token' => env('LARA_CEP_PROVIDER_CEP_ABERTO_TOKEN', ''),
        ],
    ],
];
```

### Comportamento assíncrono

- `async = true`: todos os provedores habilitados são consultados em paralelo; a primeira resposta com sucesso vence.
- `async = false`: os provedores são consultados na ordem configurada até um retornar sucesso.

### Comportamento de cache

- a chave de cache usa o prefixo configurado + CEP normalizado
- o TTL é totalmente configurável (`cache.ttl`)
- o cache pode ser desabilitado (`cache.enabled = false`)

## Tratamento de Erros

```php
use PauloHortelan\LaraCep\Exceptions\CepLookupException;
use PauloHortelan\LaraCep\Exceptions\InvalidCepException;

try {
    $address = LaraCep::find('99999999');
} catch (InvalidCepException $e) {
    // formato de CEP inválido
} catch (CepLookupException $e) {
    // todos os provedores falharam
    $details = $e->toArray();
}
```

## Testes

```bash
composer test
```

## Licença

MIT. Veja [LICENSE.md](LICENSE.md).
