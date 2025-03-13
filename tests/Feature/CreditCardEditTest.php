<?php

namespace App\Tests\Feature;

use App\Controller\CreditCardEditController;
use App\Entity\{Bank, CreditCard, CreditCardEdit, User};
use App\Enum\CardTypeEnum;
use Doctrine\ORM\EntityManagerInterface;
use Faker\{Factory, Generator};
use Symfony\Bundle\FrameworkBundle\{KernelBrowser, Test\WebTestCase};
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CreditCardEditTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $entityManager;
    private CreditCard $creditCard;
    private User $user;
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
        $this->user = $this->createUser(['email' => $this->faker->email(), 'password' => '123456']);
        $this->creditCard = $this->createCreditCard($this->bank);
        $this->entityManager->flush();
    }

    /**
     * @test
     * @see CreditCardEditController::show()
     * @see CreditCardEditController::update()
     * @group feature_test
     *
     * @return void
     */
    public function testEditCreditCardForm(): void
    {
        $this->client->loginUser($this->user);

        $crawler = $this->client->request('GET', '/credit-cards/' . $this->creditCard->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save Changes')->form();
        $this->assertNotNull($form);

        $name = $this->faker->name();
        $description = $this->faker->sentence(20);
        $annualCharges = 150.0;

        $newData = [
            'credit_card_edit' => [
                'name' => $name,
                'description' => $description,
                'annualCharges' => $annualCharges
            ]
        ];

        $this->client->request('POST', '/credit-cards/' . $this->creditCard->getId() . '/edit', $newData);
        $this->assertResponseRedirects('/credit-cards');

        $edit = $this->entityManager->getRepository(CreditCardEdit::class)->findOneBy([
            'creditCard' => $this->creditCard,
            'user' => $this->user
        ]);

        $this->assertNotNull($edit);
        $this->assertEquals($name, $edit->getName());
        $this->assertEquals($description, $edit->getDescription());
        $this->assertEquals($annualCharges, $edit->getAnnualCharges());
    }

    /**
     * @test
     * @see CreditCardEditController::update()
     * @group feature_test
     *
     * @return void
     */
    public function testUpdateCreditCardWithInvalidData(): void
    {
        $this->client->loginUser($this->user);
        $formData = [
            'credit_card_edit' => [
                'name' => '',
                'description' => '',
                'annualCharges' => -100,
            ]
        ];

        $this->client->request('POST', '/credit-cards/' . $this->creditCard->getId() . '/edit', $formData);
        $this->assertResponseStatusCodeSame(422);

        $crawler = new Crawler($this->client->getResponse()->getContent());
        $errorList = $crawler->filter('.alert-danger li');


        $errors = $errorList->each(function ($node) {return $node->text();});

        $this->assertCount(3, $errorList);
        $this->assertContains('The name must be at least 3 characters long', $errors);
        $this->assertContains('The description must be at least 5 characters long', $errors);
        $this->assertContains('The annual charges must be greater than or equal to 0', $errors);
    }

    /**
     * @test
     * @see CreditCardEditController::show()
     * @group feature_test
     *
     * @return void
     */
    public function testUpdateCreditCardWithNonExistentCard(): void
    {
        $this->client->request('GET', '/credit-cards/999999/edit');
        $this->assertResponseStatusCodeSame(302);
    }

    /**
     * @test
     * @see CreditCardEditController::show()
     * @group feature_test
     *
     * @return void
     */
    public function testEditRequiresAuthentication(): void
    {
        $this->client->request('GET', '/credit-cards/' . $this->creditCard->getId() . '/edit');
        $this->assertResponseRedirects('/login');
    }

    /**
     * @test
     * @see CreditCardEditController::update()
     * @group feature_test
     *
     * @return void
     */
    public function testCannotEditOtherUsersCreditCard(): void
    {
        $this->createCreditCardEdit($this->user, $this->creditCard);
        $this->entityManager->flush();

        $otherUser = $this->createUser(['email' => $this->faker->email, 'password' => '123456']);
        $this->entityManager->flush();
        $this->client->loginUser($otherUser);

        $this->client->request('POST', '/credit-cards/' . $this->creditCard->getId() . '/edit', [
            'credit_card_edit' => [
                'name' => 'Unauthorized Edit',
                'description' => 'This should not work',
                'annualCharges' => 200.0
            ]
        ]);
        $this->assertResponseStatusCodeSame(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     * @see CreditCardEditController::show()
     * @group feature_test
     *
     * @return void
     */
    public function testEditFormShowsExistingUserCreditCard(): void
    {
        $creditEditCard = $this->createCreditCardEdit($this->user, $this->creditCard);
        $this->entityManager->flush();

        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', '/credit-cards/' . $this->creditCard->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        $form = $crawler->selectButton('Save Changes')->form();
        $this->assertEquals($creditEditCard->getName(), $form['credit_card_edit[name]']->getValue());
        $this->assertEquals($creditEditCard->getDescription(), $form['credit_card_edit[description]']->getValue());
        $this->assertEquals($creditEditCard->getAnnualCharges(), $form['credit_card_edit[annualCharges]']->getValue());
    }

    /**
     * @test
     * @see CreditCardEditController::show()
     * @group feature_test
     *
     * @return void
     */
    public function testEditFormShowsEmptyFormWhenNoExistingEdit(): void
    {
        $this->client->loginUser($this->user);
        $crawler = $this->client->request('GET', '/credit-cards/' . $this->creditCard->getId() . '/edit');
        $this->assertResponseIsSuccessful();

        // Check if the form is empty
        $form = $crawler->selectButton('Save Changes')->form();
        $this->assertEquals('', $form['credit_card_edit[name]']->getValue());
        $this->assertEquals('', $form['credit_card_edit[description]']->getValue());
        $this->assertEquals('', $form['credit_card_edit[annualCharges]']->getValue());
    }

    /**
     * @param Bank $bank
     * @return CreditCard
     */
    private function createCreditCard(Bank $bank): CreditCard
    {
        $creditCard = new CreditCard();
        $creditCard->setCardType(CardTypeEnum::CREDIT);
        $creditCard->setInformation($this->generateSampleHtml());
        $creditCard->setName($this->faker->name());
        $creditCard->setExternalId(rand(3, 5));
        $creditCard->setImageUrl($this->faker->imageUrl());
        $creditCard->setWebsite($this->faker->url());
        $creditCard->setAnnualEquivalentRate(15.5);
        $creditCard->setAnnualCharges(100);
        $creditCard->setBank($bank);
        $creditCard->setHasRewardProgram($this->faker->boolean);

        $this->entityManager->persist($creditCard);
        return $creditCard;
    }

    /**
     * @param User $user
     * @param CreditCard $creditCard
     * @return CreditCardEdit
     */
    private function createCreditCardEdit(User $user, CreditCard $creditCard): CreditCardEdit
    {
        $creditCardEdit = new CreditCardEdit();
        $creditCardEdit->setUser($user);
        $creditCardEdit->setCreditCard($creditCard);
        $creditCardEdit->setName($this->faker->name());
        $creditCardEdit->setAnnualCharges(150);
        $creditCardEdit->setDescription($this->faker->text());
        $this->entityManager->persist($creditCardEdit);

        return $creditCardEdit;
    }

    /**
     * @return Bank
     */
    private function createBank(): Bank
    {
        $bank = new Bank();
        $bank->setName($this->faker->company());
        $bank->setCode($this->faker->unique()->word());
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

    private function createUser(array $data): User
    {
        $user = new User();
        /** @var UserPasswordHasherInterface $passwordHasher */
        $passwordHasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setEmail($data['email']);
        $user->setPassword($passwordHasher->hashPassword($user, $data['password']));

        $this->entityManager->persist($user);
        return $user;
    }

    protected function tearDown(): void
    {
        $this->entityManager->createQueryBuilder()->delete(CreditCard::class, 'c')->getQuery()->execute();
        $this->entityManager->createQueryBuilder()->delete(Bank::class, 'b')->getQuery()->execute();
        $this->entityManager->createQueryBuilder()->delete(CreditCardEdit::class, 'ce')->getQuery()->execute();
        $this->entityManager->createQueryBuilder()->delete(User::class, 'u')->getQuery()->execute();
        $this->entityManager->close();
        unset($this->entityManager);

        parent::tearDown();
    }
}
