<?php

namespace App\Repository;

use Exception;
use RuntimeException;
use SimpleXMLElement;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\{
    ClientExceptionInterface,
    RedirectionExceptionInterface,
    ServerExceptionInterface,
    TransportExceptionInterface
};
use Symfony\Contracts\HttpClient\{HttpClientInterface, ResponseInterface};

readonly class HttpRepository
{
    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    /**
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function get(string $url, array $queryParams = []): array
    {
        try {
            $response = $this->httpClient->request('GET', $url, ['query' => $queryParams]);
            if ($response->getStatusCode() !== Response::HTTP_OK) {
                throw new RuntimeException('Failed to fetch data from API: ' . $response->getContent(false));
            }

            return $this->prepareResponse($response);
        } catch (Exception $e) {
            throw new RuntimeException('Error fetching data: ' . $e->getMessage());
        }
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function prepareResponse(ResponseInterface $response): array
    {
        $xmlContent = $response->getContent();
        $xml = simplexml_load_string($xmlContent);
        if ($xml === false) {
            throw new RuntimeException('Failed to parse XML response');
        }
        return $this->prepareXmlContent($xml);
    }

    private function prepareXmlContent(SimpleXMLElement $xml): array
    {
        $array = [];
        foreach ($xml->children() as $element) {
            $item = [];
            foreach ($element->attributes() as $key => $value) {
                $item[$key] = (string) $value;
            }
            foreach ($element as $key => $value) {
                if ($value->count() > 0) {
                    $item[$key] = $this->prepareXmlContent($value);
                } else {
                    $item[$key] = (string) $value;
                }
            }
            $array[] = $item;
        }

        return $array;
    }
}
