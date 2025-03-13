<?php

namespace App\Tests\Unit\Service;

use App\Entity\{Bank, CreditCard, User};
use App\Enum\CardTypeEnum;
use App\Repository\CreditCardRepository;
use App\Service\CreditCardService;
use PHPUnit\Framework\TestCase;

class CreditCardServiceTest extends TestCase
{
    private CreditCardService $service;
    private CreditCardRepository $repository;
    private User $user;
    private CreditCard $creditCard;
    private Bank $bank;

    /**
     * Set up test data
     */
    protected function setUp(): void
    {
        $this->repository = $this->createMock(CreditCardRepository::class);
        $this->service = new CreditCardService($this->repository);
        $this->setupTestData();
    }

    /**
     * @test
     * @group unit
     * @see CreditCardService::findByFilters()
     */
    public function testFindByFiltersWithValidFilters(): void
    {
        $filters = [
            'cardType' => CardTypeEnum::CREDIT,
            'bank' => [1],
            'AERMin' => '15.5',
            'AERMax' => '30.0',
            'annualChargesMin' => '50',
            'annualChargesMax' => '200'
        ];
        $this->setupRepositoryForValidFilters();

        $result = $this->service->findByFilters($filters, $this->user);

        $this->assertCount(1, $result);
        $this->assertSame($this->creditCard, $result[0]);
    }

    /**
     * @test
     * @group unit
     * @see CreditCardService::findByFilters()
     */
    public function testFindByFiltersWithEmptyFilters(): void
    {
        $filters = [];
        $this->setupRepositoryForEmptyFilters();

        $result = $this->service->findByFilters($filters);

        $this->assertEmpty($result);
    }

    /**
     * @test
     * @group unit
     * @see CreditCardService::findByFilters()
     */
    public function testFindByFiltersWithUser(): void
    {
        $filters = ['cardType' => CardTypeEnum::CREDIT];
        $this->setupRepositoryForUserFilters();

        $result = $this->service->findByFilters($filters, $this->user);

        $this->assertCount(1, $result);
        $this->assertSame($this->creditCard, $result[0]);
    }

    /**
     * @test
     * @group unit
     * @see CreditCardService::getStats()
     */
    public function testGetStatsReturnsCorrectStructure(): void
    {
        $this->setupRepositoryForStats();

        $stats = $this->service->getStats($this->user);

        $this->assertArrayHasKey('cardTypes', $stats);
        $this->assertArrayHasKey('taeRanges', $stats);
        $this->assertArrayHasKey('banks', $stats);

        $this->assertEquals(1, $stats['cardTypes']['credit']);
        $this->assertEquals(1, $stats['cardTypes']['debit']);

        $this->assertEquals(1, $stats['taeRanges']['below_15']);
        $this->assertEquals(1, $stats['taeRanges']['15_to_30']);
        $this->assertEquals(1, $stats['taeRanges']['above_30']);

        $this->assertCount(1, $stats['banks']);
        $this->assertEquals('Test Bank', $stats['banks'][0]['name']);
        $this->assertEquals(5, $stats['banks'][0]['count']);
    }

    /**
     * Set up repository for valid filters test
     */
    private function setupRepositoryForValidFilters(): void
    {
        $this->repository->expects($this->once())
            ->method('findByFilters')
            ->with(
                $this->callback(function ($validatedFilters) {
                    return $validatedFilters['cardType'] === CardTypeEnum::CREDIT
                        && $validatedFilters['bank'] === [1]
                        && $validatedFilters['AERMin'] === 15.5
                        && $validatedFilters['AERMax'] === 30.0
                        && $validatedFilters['annualChargesMin'] === 50.0
                        && $validatedFilters['annualChargesMax'] === 200.0;
                }),
                $this->user
            )
            ->willReturn([$this->creditCard]);
    }

    /**
     * Set up repository for empty filters test
     */
    private function setupRepositoryForEmptyFilters(): void
    {
        $expectedValidatedFilters = [
            'cardType' => null,
            'bank' => null,
            'AERMin' => null,
            'AERMax' => null,
            'annualChargesMin' => null,
            'annualChargesMax' => null
        ];

        $this->repository->expects($this->once())
            ->method('findByFilters')
            ->with($expectedValidatedFilters, null)
            ->willReturn([]);
    }

    /**
     * Set up repository for user filters test
     */
    private function setupRepositoryForUserFilters(): void
    {
        $this->repository->expects($this->once())
            ->method('findByFilters')
            ->with(
                $this->callback(function ($validatedFilters) {
                    return $validatedFilters['cardType'] === CardTypeEnum::CREDIT;
                }),
                $this->user
            )
            ->willReturn([$this->creditCard]);
    }

    /**
     * Set up repository for stats test
     */
    private function setupRepositoryForStats(): void
    {
        $creditCard = clone $this->creditCard;
        $creditCard->setCardType(CardTypeEnum::CREDIT);
        $debitCard = clone $this->creditCard;
        $debitCard->setCardType(CardTypeEnum::DEBIT);

        $this->repository->method('findBy')
            ->willReturnCallback(function ($criteria) use ($creditCard, $debitCard) {
                if ($criteria['cardType'] === CardTypeEnum::CREDIT && $criteria['isActive'] === true) {
                    return [$creditCard];
                }
                if ($criteria['cardType'] === CardTypeEnum::DEBIT && $criteria['isActive'] === true) {
                    return [$debitCard];
                }
                return [];
            });

        $this->repository->method('applyUserEdits')
            ->willReturnCallback(function ($cards) {
                return $cards;
            });

        $this->repository->method('findByFilters')
            ->willReturnCallback(function ($filters, $user) {
                if ($user) {
                    return [$this->creditCard];
                }
                return [];
            });

        $this->repository->method('countByAnnualEquivalentRateRange')
            ->willReturnCallback(function ($min, $max, $cardType, $user) {
                if ($user) {
                    return 1;
                }
                if ($min === 0 && $max === 15) return 5;
                if ($min === 15 && $max === 30) return 3;
                if ($min === 30 && $max === null) return 2;
                return 0;
            });

        $this->repository->method('getBankStats')
            ->willReturn([
                ['id' => 1, 'name' => 'Test Bank', 'cardCount' => 5]
            ]);
    }

    /**
     * Set up test entities
     */
    private function setupTestData(): void
    {
        $this->user = $this->createTestUser();
        $this->bank = $this->createTestBank();
        $this->creditCard = $this->createTestCreditCard();
    }

    /**
     * Create a test user
     */
    private function createTestUser(): User
    {
        $user = new User();
        $user->setEmail('test@example.com');
        return $user;
    }

    /**
     * Create a test bank
     */
    private function createTestBank(): Bank
    {
        $bank = new Bank();
        $bank->setName('Test Bank');
        $bank->setCode('TEST');
        return $bank;
    }

    /**
     * Create a test credit card
     */
    private function createTestCreditCard(): CreditCard
    {
        $card = new CreditCard();
        $card->setName('Test Card');
        $card->setBank($this->bank);
        $card->setCardType(CardTypeEnum::CREDIT);
        $card->setExternalId(1);
        $card->setAnnualEquivalentRate(15.5);
        $card->setFirstYearFee(50.0);
        $card->setAnnualCharges(100.0);
        $card->setIncentiveAmount(200.0);
        $card->setHasRewardProgram(true);
        $card->setHasInsurance(true);
        $card->setAtmFreeDomestic(true);
        $card->setWebsite('https://test.com');
        $card->setRating(4.0);
        $card->setInformation('Test information');
        $card->setIsActive(true);
        return $card;
    }
}
