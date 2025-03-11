<?php

namespace App\Tests\Feature;

use App\Entity\{Bank, CreditCard};
use App\Enum\CardTypeEnum;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Faker\{Factory, Generator};
use Symfony\Bundle\FrameworkBundle\{KernelBrowser, Test\WebTestCase};

class CreditCardTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private CreditCard $creditCard;
    private Generator $faker;
    private Bank $bank;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->faker = Factory::create();
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        $this->bank = $this->createBank();

        $this->creditCard = $this->createCreditCard(['bank' => $this->bank]);

        $this->createCreditCard([
            'name' => 'High Rate Card',
            'cardType' => CardTypeEnum::DEBIT,
            'annualEquivalentRate' => 35.0,
            'annualCharges' => 200.0,
            'hasRewardProgram' => false,
            'bank' => $this->bank,
            'externalId' => rand(6, 8)
        ]);

        $this->entityManager->flush();
    }

    public function testListCreditCards(): void
    {
        $crawler = $this->client->request('GET', '/credit-cards');
        $this->assertResponseIsSuccessful();

        $cards = $crawler->filter('.col-md-9 .card');
        $this->assertCount(2, $cards);

        $firstCard = $cards->first();
        $this->assertStringContainsString($this->creditCard->getName(), $firstCard->filter('h5.card-title')->text());
        $this->assertStringContainsString($this->bank->getName(), $firstCard->filter('p.card-text')->text());
        $this->assertStringContainsString('15.5%', $firstCard->filter('table')->text());
        $this->assertStringContainsString('100€', $firstCard->filter('table')->text());
    }

    public function testListCreditCardsWithCardTypeFilter(): void
    {
        $crawler = $this->client->request('GET', '/credit-cards', ['card_type' => CardTypeEnum::CREDIT->value]);
        $this->assertResponseIsSuccessful();

        $cards = $crawler->filter('.col-md-9 .card');
        $this->assertCount(1, $cards);
        $this->assertStringContainsString($this->creditCard->getName(), $cards->first()->filter('h5.card-title')->text());
    }

    public function testListCreditCardsWithAERFilter(): void
    {
        $crawler = $this->client->request('GET', '/credit-cards', [
            'min_aer' => 30,
            'max_aer' => 40
        ]);

        $this->assertResponseIsSuccessful();

        $cards = $crawler->filter('.col-md-9 .card');
        $this->assertCount(1, $cards);
        $this->assertStringContainsString('High Rate Card', $cards->first()->filter('h5.card-title')->text());
    }

    public function testListCreditCardsWithMultipleFilters(): void
    {
        $crawler = $this->client->request('GET', '/credit-cards', [
            'card_type' => 'credit',
            'bank' => [$this->bank->getId()],
            'min_aer' => 10,
            'max_aer' => 20,
            'min_annual_charges' => 50,
            'max_annual_charges' => 150
        ]);

        $this->assertResponseIsSuccessful();

        $cards = $crawler->filter('.col-md-9 .card');
        $this->assertCount(1, $cards);
        $this->assertStringContainsString($this->creditCard->getName(), $cards->first()->filter('h5.card-title')->text());
    }

    public function testStatisticsDisplay(): void
    {
        $crawler = $this->client->request('GET', '/credit-cards');
        $this->assertResponseIsSuccessful();

        $stats = $crawler->filter('.col-md-3 .card:last-child .card-body');

        $cardTypes = $stats->filter('ul.list-unstyled')->first();
        $this->assertStringContainsString('Credit Cards: 1', $cardTypes->text());
        $this->assertStringContainsString('Debit Cards: 1', $cardTypes->text());

        $aerRanges = $stats->filter('ul.list-unstyled')->last();
        $this->assertStringContainsString('15% - 30%: 1', $aerRanges->text());
        $this->assertStringContainsString('Above 30%: 1', $aerRanges->text());
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->createQueryBuilder()->delete(CreditCard::class, 'c')->getQuery()->execute();
        $this->entityManager->createQueryBuilder()->delete(Bank::class, 'b')->getQuery()->execute();

        $this->entityManager->close();
        unset($this->entityManager);
    }

    private function createCreditCard(array $data = []): CreditCard
    {
        $defaults = [
            'cardType' => CardTypeEnum::CREDIT,
            'information' => $this->generateSampleHtml(),
            'name' => $this->faker->name(),
            'externalId' => rand(3, 5),
            'imageUrl' => $this->faker->imageUrl(640, 480),
            'website' => $this->faker->url(),
            'annualEquivalentRate' => 15.5,
            'annualCharges' => 100.0,
            'hasRewardProgram' => true,
            'bank' => null,
            'createdAt' => new DateTimeImmutable(),
            'updatedAt' => new DateTimeImmutable()
        ];

        $data = array_merge($defaults, $data);

        $creditCard = new CreditCard();
        $creditCard->setCardType($data['cardType']);
        $creditCard->setInformation($data['information']);
        $creditCard->setName($data['name']);
        $creditCard->setExternalId($data['externalId']);
        $creditCard->setImageUrl($data['imageUrl']);
        $creditCard->setWebsite($data['website']);
        $creditCard->setAnnualEquivalentRate($data['annualEquivalentRate']);
        $creditCard->setAnnualCharges($data['annualCharges']);
        $creditCard->setBank($data['bank']);
        $creditCard->setHasRewardProgram($data['hasRewardProgram']);
        $creditCard->setCreatedAt($data['createdAt']);
        $creditCard->setUpdatedAt($data['updatedAt']);

        $this->entityManager->persist($creditCard);
        return $creditCard;
    }

    private function createBank(): Bank
    {
        $bank = new Bank();
        $bank->setName($this->faker->company());
        $bank->setCode($this->faker->unique()->word());
        $bank->setCreatedAt(new DateTimeImmutable());
        $bank->setUpdatedAt(new DateTimeImmutable());
        $this->entityManager->persist($bank);
        return $bank;
    }

    private function generateSampleHtml(): string
    {
        return '<div class="card-info">
            <h3>' . $this->faker->sentence() . '</h3>
            <p>' . $this->faker->paragraph() . '</p>
            <ul>
                <li>' . $this->faker->sentence() . '</li>
                <li>' . $this->faker->sentence() . '</li>
            </ul>
        </div>';
    }
}
