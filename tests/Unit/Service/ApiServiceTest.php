<?php

namespace App\Tests\Unit\Service;

use App\Repository\{BankRepository, CreditCardRepository, HttpRepository};
use App\Service\ApiService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Symfony\Component\HttpClient\{MockHttpClient, Response\MockResponse};
use Symfony\Contracts\HttpClient\Exception\{
    ClientExceptionInterface,
    RedirectionExceptionInterface,
    ServerExceptionInterface,
    TransportExceptionInterface
};
use Symfony\Component\HttpFoundation\Response;

class ApiServiceTest extends TestCase
{
    private ApiService $service;
    private MockHttpClient $httpClient;

    protected function setUp(): void
    {
        $this->httpClient = new MockHttpClient();
        $httpRepository = new HttpRepository($this->httpClient);
        $this->service = new ApiService(
            $httpRepository,
            $this->createMock(CreditCardRepository::class),
            $this->createMock(BankRepository::class),
            $this->createMock(EntityManagerInterface::class)
        );
    }

    /**
     * @test
     * @group unit
     * @see ApiService::fetchAndUpdateCreditCards()
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testFetchCreditCardData(): void
    {
        $xmlResponse = $this->getSampleXml();

        $this->httpClient->setResponseFactory([
            new MockResponse($xmlResponse, [
                'http_code' => Response::HTTP_OK,
                'response_headers' => ['Content-Type: application/xml'],
            ])
        ]);

        $result = $this->service->fetchAndUpdateCreditCards();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('added', $result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('skipped', $result);
    }

    /**
     * @test
     * @group unit
     * @see ApiService::fetchAndUpdateCreditCards()
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testFetchCreditCardDataWithError(): void
    {
        $this->httpClient->setResponseFactory([
            new MockResponse('Invalid XML', [
                'http_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'response_headers' => ['Content-Type: application/xml'],
            ])
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch data from API');

        $this->service->fetchAndUpdateCreditCards();
    }

    /**
     * @test
     * @group unit
     * @see ApiService::fetchAndUpdateCreditCards()
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testUpdateCreditCardData(): void
    {
        $xmlResponse = $this->getSampleXml();

        $this->httpClient->setResponseFactory([
            new MockResponse($xmlResponse, [
                'http_code' => Response::HTTP_OK,
                'response_headers' => ['Content-Type: application/xml'],
            ])
        ]);

        $result = $this->service->fetchAndUpdateCreditCards();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('added', $result);
        $this->assertArrayHasKey('updated', $result);
        $this->assertArrayHasKey('skipped', $result);
    }

    /**
     * @test
     * @group unit
     * @see ApiService::fetchAndUpdateCreditCards()
     *
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function testUpdateCreditCardDataWithError(): void
    {
        $this->httpClient->setResponseFactory([
            new MockResponse('Invalid XML', [
                'http_code' => Response::HTTP_BAD_REQUEST,
                'response_headers' => ['Content-Type: application/xml'],
            ])
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch data from API');

        $this->service->fetchAndUpdateCreditCards();
    }

    /**
     * Returns a sample XML response for testing
     *
     * @return string
     */
    private function getSampleXml(): string
    {
        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<products>
    <product>
        <id>1</id>
        <produkt>Test Card</produkt>
        <bank>Test Bank</bank>
        <logo>http://example.com/logo.png</logo>
        <cc_provider>1</cc_provider>
        <gc_atmfree_domestic>1</gc_atmfree_domestic>
        <link>http://example.com</link>
        <anmerkungen>Test Information</anmerkungen>
        <incentive_amount>100</incentive_amount>
        <bewertung>4.5</bewertung>
        <sollzins>15,5</sollzins>
        <gebuehrenjahr1>0</gebuehrenjahr1>
        <dauerhaft>99,9</dauerhaft>
        <insurances>1</insurances>
        <bonusprogram>1</bonusprogram>
        <cardtype_text>credit</cardtype_text>
    </product>
</products>
XML;
    }
}
