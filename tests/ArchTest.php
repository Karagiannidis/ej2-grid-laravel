<?php

declare(strict_types=1);

arch('enums are string backed')
    ->expect('Karagiannidis\Ej2Grid\Enums')
    ->toBeEnums()
    ->toBeStringBackedEnums();
