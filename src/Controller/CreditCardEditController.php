<?php

namespace App\Controller;

use App\Entity\CreditCard;
use App\Form\CreditCardEditType;
use App\Service\CreditCardEditService;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/credit-cards')]
#[IsGranted('ROLE_USER')]
class CreditCardEditController extends AbstractController
{
    public function __construct(private readonly CreditCardEditService $editService)
    {
    }

    /**
     * @throws Exception
     */
    #[Route('/{id}/edit', name: 'app_credit_card_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, CreditCard $creditCard): Response
    {
        $form = $this->createForm(CreditCardEditType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();
            $data = [];

            $reflection = new \ReflectionObject($formData);
            foreach ($reflection->getProperties() as $property) {
                $property->setAccessible(true);
                $value = $property->getValue($formData);
                if ($value !== null) {
                    $data[$property->getName()] = $value;
                }
            }

            $editedBy = $this->getUser()?->getUserIdentifier() ?? 'anonymous';
            $edit = $this->editService->createEdit($creditCard, $data, $editedBy);

            if ($edit !== null) {
                $this->addFlash('success', 'Credit card details updated successfully.');
                return $this->redirectToRoute('app_credit_cards');
            } else {
                $this->addFlash('warning', 'No changes were made to the credit card.');
            }
        }

        return $this->render('credit_card/edit.html.twig', [
            'creditCard' => $creditCard,
            'form' => $form,
        ]);
    }
}
