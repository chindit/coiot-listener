<?php

namespace App\Event;

use App\Model\ShellyStatus;
use Symfony\Contracts\EventDispatcher\Event;

final class StatusUpdateEvent extends Event
{
    public const NAME = 'status.update';

    public function __construct(public readonly ShellyStatus $status){}
}