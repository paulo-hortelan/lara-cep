<?php

declare(strict_types=1);

namespace PauloHortelan\LaraCep\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \PauloHortelan\LaraCep\Address find(string|int $cep)
 */
final class LaraCep extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'lara-cep';
    }
}
