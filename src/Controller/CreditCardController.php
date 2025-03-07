<?php

namespace App\Controller;

use App\Service\CreditCardService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;

class CreditCardController extends AbstractController
{
    #[Route('/credit-cards', name: 'app_credit_cards')]
    public function index(Request $request, CreditCardService $creditCardService): Response
    {
        $filters = [
            'cardType' => $request->query->get('card_type'),
            'bank' => $request->query->all('bank'),
            'AERMin' => $request->query->get('min_aer'),
            'AERMax' => $request->query->get('max_aer'),
            'annualChargesMin' => $request->query->get('min_annual_charges'),
            'annualChargesMax' => $request->query->get('max_annual_charges'),
        ];

        $creditCards = $creditCardService->findByFilters($filters, $this->getUser());
        $stats = $creditCardService->getStats($this->getUser());

        return $this->render('credit_card/index.html.twig', [
            'creditCards' => $creditCards,
            'stats' => $stats,
            'filters' => $filters
        ]);
    }
}
