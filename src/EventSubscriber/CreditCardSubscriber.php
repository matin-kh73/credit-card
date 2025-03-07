<?php

namespace App\EventSubscriber;

use App\Entity\CreditCard;
use App\Service\CreditCardEditService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

readonly class CreditCardSubscriber implements EventSubscriberInterface
{
    public function __construct(private CreditCardEditService $editService)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [Events::postLoad => 'onPostLoad'];
    }

    public function onPostLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof CreditCard) {
            $entity->setEditService($this->editService);
        }
    }
}
