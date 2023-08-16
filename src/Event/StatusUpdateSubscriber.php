<?php

namespace App\Event;

use App\Entity\Shelly;
use App\Enums\ShellyCodes;
use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StatusUpdateSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $influxToken,
        private readonly string $influxBucket,
        private readonly string $influxOrg
    ){}
    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            StatusUpdateEvent::NAME => 'onUpdate',
            StatusUpdateRpcEvent::NAME => 'onRpcUpdate'
        ];
    }

    public function onUpdate(StatusUpdateEvent $event):void
    {
        try {
            $shellyEvent = (new Shelly())
                ->setDeviceId($event->status->deviceId)
                ->setType('plug')
                ->setPower($event->status->statuses[ShellyCodes::power_W->name])
                ->setTemperature($event->status->statuses?->{ShellyCodes::deviceTemp_C->name} ?? 0.0)
                ->setTotal($event->status->statuses[ShellyCodes::energy_Wmin->name]);
            $this->saveToInflux($shellyEvent);
        } catch (\Throwable $t) {
            $this->logger->error($t);
            $this->logger->error(sprintf('Unable to save Shelly event.  Received error is: %s', $t->getMessage()), context: ['id' => $event->status->deviceId, 'statuses' => $event->status->statuses]);
        }
    }

    private function saveToInflux(Shelly $shelly): void
    {
        $client = new Client([
            "url" => "http://localhost:8086",
            "token" => $this->influxToken,
            "bucket" => $this->influxBucket,
            "org" => $this->influxOrg,
            "precision" => WritePrecision::S
        ]);
        if ($shelly->getType() === 'plug') {
            $client->createWriteApi()
                ->write(sprintf('plug,device_id=%s power=%f,temperature=%f,total=%u',
                    $shelly->getDeviceId(),
                    $shelly->getPower(),
                    $shelly->getTemperature(),
                    $shelly->getTotal()));
        } else {
            $client->createWriteApi()
                ->write(sprintf('sensor,device_id=%s temperature=%f,humidity=%f,battery=%f',
                    $shelly->getDeviceId(),
                    $shelly->getData()['temperature'],
                    $shelly->getData()['humidity'],
                    $shelly->getData()['battery']['percent']));
        }
        $client->close();
    }

    public function onRpcUpdate(StatusUpdateRpcEvent $event): void
    {
        $this->saveToInflux($event->shelly);
    }
}