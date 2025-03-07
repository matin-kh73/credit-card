<?php

namespace App\Repository;

use App\Entity\CreditCard;
use App\Entity\CreditCardEdit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CreditCardEdit>
 *
 * @method CreditCardEdit|null find($id, $lockMode = null, $lockVersion = null)
 * @method CreditCardEdit|null findOneBy(array $criteria, array $orderBy = null)
 * @method CreditCardEdit[]    findAll()
 * @method CreditCardEdit[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CreditCardEditRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditCardEdit::class);
    }

    public function save(CreditCardEdit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CreditCardEdit $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return CreditCardEdit[] Returns an array of CreditCardEdit objects
     */
    public function findByCreditCardOrderedByDate(CreditCard $creditCard): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.creditCard = :creditCard')
            ->setParameter('creditCard', $creditCard)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
} 