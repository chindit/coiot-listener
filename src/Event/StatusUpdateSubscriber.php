<?php

namespace App\Event;

use App\Entity\Shelly;
use App\Enums\ShellyCodes;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StatusUpdateSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly LoggerInterface $logger){}
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
                ->setTemperature($event->status->statuses[ShellyCodes::deviceTemp_C->name])
                ->setTotal($event->status->statuses[ShellyCodes::energy_Wmin->name]);
            $this->entityManager->persist($shellyEvent);
            $this->entityManager->flush();
        } catch (\Throwable $t) {
            $this->logger->error(sprintf('Unable to save Shelly event.  Received error is: %s', $t->getMessage()), context: ['id' => $event->status->deviceId, 'statuses' => $event->status->statuses]);
        }
    }

    public function onRpcUpdate(StatusUpdateRpcEvent $event): void
    {
        $this->entityManager->persist($event->shelly);
        $this->entityManager->flush();
    }
}