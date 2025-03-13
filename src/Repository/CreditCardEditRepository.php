<?php

namespace App\Repository;

use App\Entity\CreditCardEdit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreditCardEdit>
 *
 * @method CreditCardEdit|null find($id, $lockMode = null, $lockVersion = null)
 * @method CreditCardEdit|null findOneBy(array $criteria, array $orderBy = null)
 * @method CreditCardEdit[] findAll()
 * @method CreditCardEdit[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CreditCardEditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditCardEdit::class);
    }

    public function create(array $data): CreditCardEdit
    {
        $creditEdit = new CreditCardEdit();
        $creditEdit->setName($data['name']);
        $creditEdit->setAnnualCharges($data['annualCharges']);
        $creditEdit->setDescription($data['description']);
        $creditEdit->setUser($data['user']);
        $creditEdit->setCreditCard($data['creditCard']);

        $this->getEntityManager()->persist($creditEdit);
        $this->getEntityManager()->flush();

        return $creditEdit;
    }

    public function remove(CreditCardEdit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
