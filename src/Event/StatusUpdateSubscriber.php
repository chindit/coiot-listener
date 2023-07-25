<?php

namespace App\Event;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StatusUpdateSubscriber implements EventSubscriberInterface
{

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            StatusUpdateEvent::NAME => 'onUpdate'
        ];
    }

    public function onUpdate(StatusUpdateEvent $event):void
    {
        dump($event);
    }
}