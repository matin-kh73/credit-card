<?php

namespace App\Repository;

use App\Entity\Bank;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BankRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Bank::class);
    }

    public function save(Bank $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Bank $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findOneByName(string $name): ?Bank
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.name = :name')
            ->setParameter('name', $name)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function create(array $data): Bank
    {
        $bank = new Bank();
        $bank->setName($data['bankName']);
        $code = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $data['bankName']));
        $bank->setCode($code);
        $bank->setIsActive(true);
        $this->getEntityManager()->persist($bank);

        return $bank;
    }

    public function update(Bank $bank, array $data): void
    {
        $bank->setName($data['bankName']);
        $bank->setCode(strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $data['bankName'])));
    }
}
