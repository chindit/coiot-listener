<?php

namespace App\Model;

/**
 * @property-read <string, float> $statuses
 */
final readonly class ShellyStatus
{
    public function __construct(
        public string $deviceId,
        public array $statuses
    ){}
}