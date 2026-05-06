<?php

declare(strict_types=1);

arch('it will not use debugging helpers')
    ->expect(['dd', 'dump', 'ray'])
    ->not->toBeUsed();
