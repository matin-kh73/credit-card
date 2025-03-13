<?php

namespace App\Service;

use App\Entity\{CreditCard, CreditCardEdit, User};
use App\Repository\CreditCardEditRepository;
use Exception;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\User\UserInterface;

readonly class CreditCardEditService
{
    public function __construct(private CreditCardEditRepository $editRepository, private Security $security)
    {
    }

    /**
     * @throws Exception
     */
    public function createEdit(CreditCard $creditCard, array $data): ?CreditCardEdit
    {
        $user = $this->security->getUser();
        $this->checkExistenceUser($user);

        $existingEdit = $this->editRepository->findOneBy(['creditCard' => $creditCard]);
        if ($existingEdit && $existingEdit->getUser() !== $user) {
            throw new Exception('You are not authorized to edit this credit card', Response::HTTP_FORBIDDEN);
        }

        $data['user'] = $user;
        $data['creditCard'] = $creditCard;

        return $this->editRepository->create($data);
    }

    /**
     * @param CreditCard $creditCard
     * @return CreditCardEdit
     *
     * @throws Exception
     */
    public function getUserCreditCard(CreditCard $creditCard): CreditCardEdit
    {
        $user = $this->security->getUser();
        $this->checkExistenceUser($user);

        $existingEdit = $this->editRepository->findOneBy(['creditCard' => $creditCard, 'user' => $user]);
        if (!$existingEdit) {
            $edit = new CreditCardEdit();
            $edit->setCreditCard($creditCard);
            $edit->setUser($user);
            return $edit;
        }

        return $existingEdit;
    }

    /**
     * @param UserInterface|null $user
     * @return void
     *
     * @throws Exception
     */
    private function checkExistenceUser(?UserInterface $user): void
    {
        if (!$user instanceof User) {
            throw new Exception('User must be logged in to create an edit');
        }
    }
}
