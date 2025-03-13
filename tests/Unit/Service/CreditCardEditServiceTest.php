<?php

namespace App\Tests\Unit\Service;

use App\Entity\{Bank, CreditCard, CreditCardEdit, User};
use App\Enum\CardTypeEnum;
use App\Repository\CreditCardEditRepository;
use App\Service\CreditCardEditService;
use Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class CreditCardEditServiceTest extends TestCase
{
    private CreditCardEditService $service;
    private CreditCardEditRepository $repository;
    private Security $security;
    private User $user;
    private CreditCard $creditCard;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(CreditCardEditRepository::class);
        $this->security = $this->createMock(Security::class);
        $this->service = new CreditCardEditService($this->repository, $this->security);

        $this->user = new User();
        $this->user->setEmail('test@example.com');

        $bank = new Bank();
        $bank->setName('Test Bank');
        $bank->setCode('TEST');

        $this->creditCard = new CreditCard();
        $this->creditCard->setName('Test Card');
        $this->creditCard->setBank($bank);
        $this->creditCard->setCardType(CardTypeEnum::CREDIT);
        $this->creditCard->setExternalId(1);
    }

    /**
     * @test
     * @group unit
     * @throws Exception
     *
     * @see CreditCardEditService::createEdit()
     */
    public function testCreateEdit(): void
    {
        $data = ['name' => 'Updated Card Name', 'description' => 'Updated description', 'annualCharges' => 150.0];

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->repository->expects($this->once())
            ->method('create')
            ->with($this->callback(function ($editData) use ($data) {
                return $editData['name'] === $data['name']
                    && $editData['description'] === $data['description']
                    && $editData['annualCharges'] === $data['annualCharges']
                    && $editData['user'] === $this->user
                    && $editData['creditCard'] === $this->creditCard;
            }))
            ->willReturn(new CreditCardEdit());

        $result = $this->service->createEdit($this->creditCard, $data);

        $this->assertInstanceOf(CreditCardEdit::class, $result);
    }

    /**
     * @test
     * @group unit
     * @throws Exception
     *
     * @see CreditCardEditService::getUserCreditCard()
     */
    public function testGetUserCreditCard(): void
    {
        $edit = new CreditCardEdit();
        $edit->setName('Edited Card Name');
        $edit->setDescription('Edited description');
        $edit->setAnnualCharges(200.0);
        $edit->setUser($this->user);
        $edit->setCreditCard($this->creditCard);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $this->user, 'creditCard' => $this->creditCard])
            ->willReturn($edit);

        $result = $this->service->getUserCreditCard($this->creditCard);

        $this->assertInstanceOf(CreditCardEdit::class, $result);
        $this->assertEquals('Edited Card Name', $result->getName());
        $this->assertEquals('Edited description', $result->getDescription());
        $this->assertEquals(200.0, $result->getAnnualCharges());
    }

    /**
     * @test
     * @group unit
     * @throws Exception
     *
     * @see CreditCardEditService::getUserCreditCard()
     */
    public function testGetUserCreditCardReturnsNewInstanceWhenNoEditExists(): void
    {
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($this->user);

        $this->repository->expects($this->once())
            ->method('findOneBy')
            ->with(['user' => $this->user, 'creditCard' => $this->creditCard])
            ->willReturn(null);

        $result = $this->service->getUserCreditCard($this->creditCard);

        $this->assertInstanceOf(CreditCardEdit::class, $result);
        $this->assertEquals('', $result->getName());
        $this->assertEquals('', $result->getDescription());
        $this->assertEquals(0.0, $result->getAnnualCharges());
        $this->assertEquals($this->user, $result->getUser());
        $this->assertEquals($this->creditCard, $result->getCreditCard());
    }
}
