<?php

namespace App\Event;

use App\Entity\Shelly;
use App\Enums\ShellyCodes;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StatusUpdateSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager){}
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
        $shellyEvent = (new Shelly())
            ->setDeviceId($event->status->deviceId)
            ->setType('plug')
            ->setPower($event->status->statuses[ShellyCodes::power_W->name])
            ->setTemperature($event->status->statuses[ShellyCodes::deviceTemp_C->name])
            ->setTotal($event->status->statuses[ShellyCodes::energy_Wmin->name]);
        $this->entityManager->persist($shellyEvent);
        $this->entityManager->flush();
    }

    public function onRpcUpdate(StatusUpdateRpcEvent $event): void
    {
        $this->entityManager->persist($event->shelly);
        $this->entityManager->flush();
    }
}