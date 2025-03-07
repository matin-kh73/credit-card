<?php

namespace App\Repository;

use App\Entity\CreditCard;
use App\Entity\CreditCardEdit;
use App\Entity\User;
use App\Enum\CardTypeEnum;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use DateTimeImmutable;

class CreditCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CreditCard::class);
    }

    public function create(array $data): CreditCard
    {
        $card = new CreditCard();
        $card
            ->setName($data['cardName'])
            ->setExternalId($data['cardId'])
            ->setCardType($data['cardType'])
            ->setFirstYearFee($data['firstYearFee'])
            ->setAnnualCharges($data['annualCharges'])
            ->setAnnualEquivalentRate($data['annualEquivalentRate'])
            ->setIncentiveAmount($data['incentive_amount'])
            ->setHasRewardProgram($data['hasRewardProgram'])
            ->setHasInsurance($data['hasInsurance'])
            ->setInformation($data['information'])
            ->setAtmFreeDomestic(false)
            ->setImageUrl($data['imageUrl'])
            ->setProvider($data['provider'])
            ->setRating($data['rating'])
            ->setWebsite($data['website'])
            ->setIsActive(true)
            ->setCreatedAt(new DateTimeImmutable())
            ->setUpdatedAt(new DateTimeImmutable());

        $this->getEntityManager()->persist($card);

        return $card;
    }

    public function update(CreditCard $card, array $data): void
    {
        $card
            ->setName($data['cardName'])
            ->setCardType($data['cardType'])
            ->setFirstYearFee($data['firstYearFee'])
            ->setAnnualCharges($data['annualCharges'])
            ->setAnnualEquivalentRate($data['annualEquivalentRate'])
            ->setHasRewardProgram($data['hasRewardProgram'])
            ->setHasInsurance($data['hasInsurance'])
            ->setInformation($data['information'])
            ->setImageUrl($data['imageUrl'])
            ->setRating($data['rating'])
            ->setWebsite($data['website'])
            ->setUpdatedAt(new DateTimeImmutable());
    }

    public function findByFilters(array $filters, ?User $user = null): array
    {
        $queryBuilder = $this->createBaseQueryBuilder()
            ->leftJoin('c.bank', 'b')
            ->leftJoin('c.edits', 'e', 'WITH', 'e.user = :user')
            ->setParameter('user', $user);

        $this->applyMainTableFilters($queryBuilder, $filters);

        $queryBuilder->orderBy('c.rating', 'DESC');
        $results = $queryBuilder->getQuery()->getResult();

        return $this->transformResults($results, $user);
    }

    private function applyMainTableFilters(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['cardType']) && $filters['cardType']) {
            $qb->andWhere('c.cardType = :cardType')
                ->setParameter('cardType', $filters['cardType']);
        }

        if (isset($filters['bank']) && $filters['bank']) {
            $qb->andWhere('b.id IN (:bankIds)')
                ->setParameter('bankIds', $filters['bank']);
        }

        if (isset($filters['AERMin']) && $filters['AERMin']) {
            $qb->andWhere('c.annualEquivalentRate >= :AERMin')
                ->setParameter('AERMin', $filters['AERMin']);
        }

        if (isset($filters['AERMax']) && $filters['AERMax']) {
            $qb->andWhere('c.annualEquivalentRate <= :AERMax')
                ->setParameter('AERMax', $filters['AERMax']);
        }

        if (isset($filters['annualChargesMin']) && $filters['annualChargesMin']) {
            $qb->andWhere('COALESCE(e.annualCharges, c.annualCharges) >= :annualChargesMin')
                ->setParameter('annualChargesMin', $filters['annualChargesMin']);
        }

        if (isset($filters['annualChargesMax']) && $filters['annualChargesMax']) {
            $qb->andWhere('COALESCE(e.annualCharges, c.annualCharges) <= :annualChargesMax')
                ->setParameter('annualChargesMax', $filters['annualChargesMax']);
        }
    }

    private function transformResults(array $results, ?User $user): array
    {
        if (!$user) {
            return $results;
        }

        $transformedCards = [];
        foreach ($results as $result) {
            if (is_array($result)) {
                $card = $result[0];
                if (isset($result['editedName']) || isset($result['editedAnnualCharges'])) {
                    $card->setName($result['editedName'] ?? $card->getName());
                    $card->setAnnualCharges($result['editedAnnualCharges'] ?? $card->getAnnualCharges());
                }
            } else {
                $card = $result;
            }
            $transformedCards[] = $card;
        }

        return $transformedCards;
    }

    public function countByAnnualEquivalentRateRange(?float $min = null, ?float $max = null, ?CardTypeEnum $cardType = null, ?User $user = null): int
    {
        $qb = $this->createBaseQueryBuilder()->select('COUNT(c.id)');

        if ($min !== null) {
            $qb->andWhere('c.annualEquivalentRate >= :min')->setParameter('min', $min);
        }

        if ($max !== null) {
            $qb->andWhere('c.annualEquivalentRate < :max')->setParameter('max', $max);
        }

        if ($cardType !== null) {
            $qb->andWhere('c.cardType = :card_type')->setParameter('card_type', $cardType);
        }

        $count = (int) $qb->getQuery()->getSingleScalarResult();
        if ($user) {
            $cards = $this->findByFilters([], $user);
            $count = count($cards);
        }

        return $count;
    }

    public function getBankStats(?User $user = null): array
    {
        $stats = $this->createBaseQueryBuilder()
            ->leftJoin('c.bank', 'b')
            ->select('b.id, b.name, COUNT(c.id) as cardCount')
            ->groupBy('b.id')
            ->orderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();

        if ($user) {
            $cards = $this->findByFilters([], $user);
            $bankCounts = [];
            foreach ($cards as $card) {
                $bankId = $card->getBank()->getId();
                if (!isset($bankCounts[$bankId])) {
                    $bankCounts[$bankId] = 0;
                }
                $bankCounts[$bankId]++;
            }

            foreach ($stats as &$stat) {
                $stat['cardCount'] = $bankCounts[$stat['id']] ?? 0;
            }
        }

        return $stats;
    }

    public function findOneByExternalId(string $externalId, ?User $user = null): ?CreditCard
    {
        $card = $this->findOneBy(['externalId' => $externalId]);

        if ($card && $user) {
            $card = $this->applyUserEdits([$card], $user)[0] ?? null;
        }

        return $card;
    }

    private function createBaseQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->where('c.isActive = :active')
            ->setParameter('active', true);
    }

    public function applyUserEdits(array $cards, User $user): array
    {
        if (empty($cards)) {
            return $cards;
        }

        $cardIds = array_map(fn(CreditCard $card) => $card->getId(), $cards);

        $edits = $this->getEntityManager()
            ->getRepository(CreditCardEdit::class)
            ->createQueryBuilder('e')
            ->where('e.creditCard IN (:cardIds)')
            ->andWhere('e.user = :user')
            ->setParameter('cardIds', $cardIds)
            ->setParameter('user', $user)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        $editsByCard = [];
        foreach ($edits as $edit) {
            $cardId = $edit->getCreditCard()->getId();
            if (!isset($editsByCard[$cardId])) {
                $editsByCard[$cardId] = $edit;
            }
        }

        foreach ($cards as $card) {
            if (isset($editsByCard[$card->getId()])) {
                $edit = $editsByCard[$card->getId()];
                $card->setName($edit->getName());
                $card->setAnnualCharges($edit->getAnnualCharges());
            }
        }

        return $cards;
    }
}
