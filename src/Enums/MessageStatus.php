<?php

namespace InnovativeSprouts\Semaphore\Enums;

use InnovativeSprouts\Semaphore\Enums\Concerns\MapsApiValues;

enum MessageStatus: string
{
    use MapsApiValues;

    case Queued = 'queued';
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';
    case Refunded = 'refunded';
}
