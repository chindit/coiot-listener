<?php

namespace App\Event;

use App\Entity\Shelly;
use Symfony\Contracts\EventDispatcher\Event;

class StatusUpdateRpcEvent extends Event
{
    public const NAME = 'status.rpc.update';
    public function __construct(public readonly Shelly $shelly){}
}