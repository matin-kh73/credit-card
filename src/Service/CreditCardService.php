<?php

namespace App\Service;

use App\Entity\User;
use App\Enum\CardTypeEnum;
use App\Repository\CreditCardRepository;

readonly class CreditCardService
{
    public function __construct(private CreditCardRepository $creditCardRepository)
    {
    }

    public function findByFilters(array $filters, ?User $user = null): array
    {
        $validatedFilters = $this->validateFilters($filters);
        return $this->creditCardRepository->findByFilters($validatedFilters, $user);
    }

    private function validateFilters(array $filters): array
    {
        $validatedFilters = [
            'cardType' => null,
            'bank' => null,
            'AERMin' => null,
            'AERMax' => null,
            'annualChargesMin' => null,
            'annualChargesMax' => null
        ];

        if (isset($filters['cardType'])) {
            $validatedFilters['cardType'] = $filters['cardType'];
        }

        if (isset($filters['bank'])) {
            $validatedFilters['bank'] = $filters['bank'];
        }

        if (isset($filters['AERMin'])) {
            $validatedFilters['AERMin'] = (float) $filters['AERMin'];
        }

        if (isset($filters['AERMax'])) {
            $validatedFilters['AERMax'] = (float) $filters['AERMax'];
        }

        if (isset($filters['annualChargesMin'])) {
            $validatedFilters['annualChargesMin'] = (float) $filters['annualChargesMin'];
        }

        if (isset($filters['annualChargesMax'])) {
            $validatedFilters['annualChargesMax'] = (float) $filters['annualChargesMax'];
        }

        return $validatedFilters;
    }

    public function getStats(?User $user = null): array
    {
        return [
            'cardTypes' => [
                'credit' => $this->countByCardType(CardTypeEnum::CREDIT, $user),
                'debit' => $this->countByCardType(CardTypeEnum::DEBIT, $user),
            ],
            'taeRanges' => [
                'below_15' => $this->countByTaeRange(0, 15, $user),
                '15_to_30' => $this->countByTaeRange(15, 30, $user),
                'above_30' => $this->countByTaeRange(30, null, $user),
            ],
            'banks' => $this->getBankStats($user),
        ];
    }

    private function countByCardType(CardTypeEnum $type, ?User $user = null): int
    {
        $cards = $this->creditCardRepository->findBy(['cardType' => $type, 'isActive' => true]);
        if ($user) {
            $cards = $this->creditCardRepository->applyUserEdits($cards, $user);
        }
        return count($cards);
    }

    private function countByTaeRange(?float $min, ?float $max, ?User $user = null): int
    {
        return $this->creditCardRepository->countByAnnualEquivalentRateRange($min, $max, null, $user);
    }

    private function getBankStats(?User $user = null): array
    {
        $results = $this->creditCardRepository->getBankStats($user);

        $stats = [];
        foreach ($results as $result) {
            $stats[] = [
                'id' => $result['id'],
                'name' => $result['name'],
                'count' => $result['cardCount'],
            ];
        }

        return $stats;
    }
}
