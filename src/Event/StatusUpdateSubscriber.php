<?php

namespace App\Event;

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
        dump($event);
    }

    public function onRpcUpdate(StatusUpdateRpcEvent $event): void
    {
        $this->entityManager->persist($event->shelly);
        $this->entityManager->flush();
    }
}