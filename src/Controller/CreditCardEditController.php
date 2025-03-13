<?php

namespace App\Controller;

use App\Entity\CreditCard;
use App\Form\CreditCardEditType;
use App\Request\CreditCardUpdateRequest;
use App\Service\CreditCardEditService;
use App\Exception\ValidationException;
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
     * Show the edit form for a credit card
     *
     * @throws Exception
     */
    #[Route('/{id}/edit', name: 'app_credit_card_edit', methods: ['GET'])]
    public function show(CreditCard $creditCard): Response
    {
        $userCreditCard = $this->editService->getUserCreditCard($creditCard);
        $form = $this->createForm(CreditCardEditType::class, $userCreditCard);

        return $this->render('credit_card/edit.html.twig', [
            'creditCard' => $creditCard,
            'form' => $form,
            'userCreditCard' => $userCreditCard
        ]);
    }

    /**
     * Handle the credit card edit form submission
     * @throws Exception
     */
    #[Route('/{id}/edit', name: 'app_credit_card_edit_post', methods: ['POST'])]
    public function update(Request $request, CreditCard $creditCard): Response
    {
        try {
            $editRequest = new CreditCardUpdateRequest($request);
            $editRequest->validate();
            $this->editService->createEdit($creditCard, $editRequest->toArray());

            $this->addFlash('success', 'Credit card details updated successfully.');
            return $this->redirectToRoute('app_credit_cards');

        } catch (ValidationException $e) {
            $form = $this->createForm(CreditCardEditType::class);

            return $this->render('credit_card/edit.html.twig', [
                'creditCard' => $creditCard,
                'form' => $form,
                'errors' => $e->getErrors()
            ], new Response('', Response::HTTP_UNPROCESSABLE_ENTITY));
        } catch (Exception $e) {
            return new Response($e->getMessage(), $e->getCode());
        }
    }
}
