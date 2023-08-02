<?php

namespace App\Entity;

use App\Repository\ShellyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ShellyRepository::class)]
class Shelly
{
    #[ORM\Column(length: 10)]
    private ?string $type = null;

    #[ORM\Column(length: 12)]
    private ?string $device_id = null;

    #[ORM\Column]
    private float $power = 0.0;

    #[ORM\Column]
    private float $temperature = 0.0;

    #[ORM\Column]
    private int $total = 0;

    #[ORM\Id]
    #[ORM\Column]
    private string $time;

    #[ORM\Column]
    private array $data = [];

    public function __construct() {
        $this->time = (new \DateTime())->format('Y-m-d H:i:s.u');
    }
    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDeviceId(): ?string
    {
        return $this->device_id;
    }

    public function setDeviceId(string $device_id): static
    {
        $this->device_id = $device_id;

        return $this;
    }

    public function getPower(): float
    {
        return $this->power;
    }

    public function setPower(float $power): static
    {
        $this->power = $power;

        return $this;
    }

    public function getTemperature(): float
    {
        return $this->temperature;
    }

    public function setTemperature(float $temperature): static
    {
        $this->temperature = $temperature;

        return $this;
    }

    public function getTotal(): int
    {
        return $this->total;
    }

    public function setTotal(int $total): static
    {
        $this->total = $total;

        return $this;
    }

    public function getTime(): ?int
    {
        return $this->time;
    }

    public function setTime(int $time): static
    {
        $this->time = $time;

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }
}
