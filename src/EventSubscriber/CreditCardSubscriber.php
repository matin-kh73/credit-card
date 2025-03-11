<?php

namespace App\EventSubscriber;

use App\Entity\CreditCard;
use App\Service\CreditCardEditService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

#[AsDoctrineListener(event: Events::postLoad)]
readonly class CreditCardSubscriber
{
    public function __construct(private CreditCardEditService $editService)
    {
    }

    public function postLoad(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($entity instanceof CreditCard) {
            $entity->setEditService($this->editService);
        }
    }
}
