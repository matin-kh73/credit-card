<?php

namespace App\Service;

use App\Entity\{Bank, CreditCard};
use App\Enum\CardTypeEnum;
use App\Repository\{BankRepository, CreditCardRepository, HttpRepository};
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Contracts\HttpClient\Exception\{
    ClientExceptionInterface,
    RedirectionExceptionInterface,
    ServerExceptionInterface,
    TransportExceptionInterface
};

class ApiService
{
    private const string API_URL = 'https://tools.financeads.net/webservice.php';

    public function __construct(
        private readonly HttpRepository $httpRepository,
        private readonly CreditCardRepository $creditCardRepository,
        private readonly BankRepository $bankRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function fetchAndUpdateCreditCards(bool $forceUpdate = false): array
    {
        $queryParams = ['wf' => '1', 'format' => 'xml', 'calc' => 'kreditkarterechner', 'country' => 'ES'];
        $rawData = $this->httpRepository->get(self::API_URL, $queryParams);

        $stats = ['total' => 0, 'added' => 0, 'updated' => 0, 'skipped' => 0];

        try {
            $this->entityManager->beginTransaction();
            foreach ($rawData as $cardData) {
                $transformedData = $this->transformCardData($cardData);

                $bank = $this->bankRepository->findOneByName($transformedData['bankName']);
                if ($bank === null) {
                    $bank = $this->bankRepository->create($transformedData);
                } elseif ($forceUpdate || $this->hasBankChanges($bank, $transformedData)) {
                    $this->bankRepository->update($bank, $transformedData);
                }

                $card = $this->creditCardRepository->findOneByExternalId($transformedData['cardId']);
                if ($card === null) {
                    $card = $this->creditCardRepository->create($transformedData);
                    $stats['added']++;
                } elseif ($forceUpdate || $this->hasCardChanges($card, $transformedData)) {
                    $this->creditCardRepository->update($card, $transformedData);
                    $stats['updated']++;
                } else {
                    $stats['skipped']++;
                }

                $card->setBank($bank);
                $this->entityManager->flush();
                $stats['total']++;
            }
            $this->entityManager->commit();
        } catch (Exception $e) {
            $this->entityManager->rollback();
            error_log('Error processing card: ' . $e->getMessage());
        }

        return $stats;
    }

    private function hasBankChanges(Bank $bank, array $data): bool
    {
        return $bank->getName() !== $data['bankName']
            || $bank->getCode() !== strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $data['bankName']));
    }

    private function hasCardChanges(CreditCard $card, array $data): bool
    {
        return $card->getName() !== $data['cardName']
            || $card->getCardType() !== $data['cardType']
            || $card->getFirstYearFee() !== $data['firstYearFee']
            || $card->getAnnualCharges() !== $data['annualCharges']
            || $card->getAnnualEquivalentRate() !== $data['annualEquivalentRate']
            || $card->hasRewardProgram() !== $data['hasRewardProgram']
            || $card->hasInsurance() !== $data['hasInsurance']
            || $card->getImageUrl() !== $data['imageUrl']
            || $card->getRating() !== $data['rating']
            || $card->getWebsite() !== $data['website'];
    }

    private function transformCardData(array $data): array
    {
        return [
            'bankName' => (string)$data['bank'],
            'cardId' => (int)$data['id'],
            'cardName' => (string)$data['produkt'],
            'imageUrl' => (string)$data['logo'],
            'provider' => (int)($data['cc_provider']),
            'atmFreeDomestic' => (bool)$data['gc_atmfree_domestic'],
            'website' => (string)$data['link'],
            'information' => html_entity_decode((string)$data['anmerkungen'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'incentive_amount' => (float)$data['incentive_amount'],
            'rating' => (float)$data['bewertung'],
            'annualEquivalentRate' => (float)str_replace(',', '.', (string)$data['sollzins']),
            'firstYearFee' => (float)str_replace(',', '.', (string)$data['gebuehrenjahr1']),
            'annualCharges' => (float)str_replace(',', '.', (string)$data['dauerhaft']),
            'hasInsurance' => (int)$data['insurances'],
            'hasRewardProgram' => (int)$data['bonusprogram'],
            'cardType' => (string)$data['cardtype_text'] === 'credit' ? CardTypeEnum::CREDIT : CardTypeEnum::DEBIT,
            'logo' => (string)$data['logo']
        ];
    }
}
