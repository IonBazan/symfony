<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\HttpClient;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @author Jérémy Romey <jeremy@free-agent.fr>
 */
class TraceableHttpClient implements HttpClientInterface
{
    protected $httpClient;
    protected $traces = [];

    /**
     * @param HttpClientInterface $httpClient A HttpClientInterface instance
     */
    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $response = $this->httpClient->request($method, $url, $options);

        $this->traces[] = [
            'request' => [
                'method' => $method,
                'url' => $url,
                'options' => $options,
            ],
            'response' => [
                'statusCode' => $response->getStatusCode(),
                'headers' => $response->getHeaders(false),
            ],
        ];

        return $response;
    }

    /**
     * {@inheritdoc}
     */
    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        $this->httpClient->stream($responses, $timeout);
    }

    public function getTraces(): array
    {
        return $this->traces;
    }
}
