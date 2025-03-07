<?php

namespace App\Service;

use App\Entity\CreditCard;
use App\Entity\CreditCardEdit;
use App\Entity\User;
use App\Repository\CreditCardEditRepository;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;

readonly class CreditCardEditService
{
    public function __construct(
        private CreditCardEditRepository $editRepository,
        private LoggerInterface $logger,
        private Security $security
    ) {
    }

    public function createEdit(CreditCard $creditCard, array $data): ?CreditCardEdit
    {
        if (!$this->hasChanges($data)) {
            return null;
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new Exception('User must be logged in to create an edit');
        }

        $edit = new CreditCardEdit();
        $edit->setCreditCard($creditCard);
        $edit->setUser($user);

        foreach ($data as $field => $value) {
            if ($value !== null) {
                $setter = 'set' . ucfirst($field);
                if (method_exists($edit, $setter)) {
                    $edit->$setter($value);
                }
            }
        }

        try {
            $this->editRepository->save($edit, true);
            return $edit;
        } catch (Exception $e) {
            $this->logger->error('Error saving edit', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getLatestEdit(CreditCard $creditCard): ?CreditCardEdit
    {
        $edits = $this->editRepository->findByCreditCardOrderedByDate($creditCard);
        return $edits[0] ?? null;
    }

    public function getEffectiveValue(CreditCard $creditCard, string $field): mixed
    {
        $latestEdit = $this->getLatestEdit($creditCard);
        $getter = 'get' . ucfirst($field);
        if ($latestEdit === null) {
            return $creditCard->$getter();
        }

        $value = $latestEdit->$getter();
        if ($value === null) {
            $getter = 'get' . ucfirst($field);
            return $creditCard->$getter();
        }

        return $value;
    }

    private function hasChanges(array $data): bool
    {
        foreach ($data as $value) {
            if ($value !== null) {
                return true;
            }
        }
        return false;
    }
}
