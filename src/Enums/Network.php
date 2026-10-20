<?php

namespace InnovativeSprouts\Semaphore\Enums;

use InnovativeSprouts\Semaphore\Enums\Concerns\MapsApiValues;

enum Network: string
{
    use MapsApiValues;

    case Globe = 'globe';
    case Smart = 'smart';
    case Sun = 'sun';
    case Dito = 'dito';
    case Tm = 'tm';
    case Tnt = 'tnt';
}
