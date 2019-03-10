<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\FrameworkBundle\DataCollector;

use Symfony\Bundle\FrameworkBundle\HttpClient\TraceableHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Jérémy Romey <jeremy@free-agent.fr>
 */
class HttpClientDataCollector extends DataCollector
{
    protected $httpClient;

    public function __construct(HttpClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if ($this->httpClient instanceof TraceableHttpClient) {
            $this->data['traces'] = $this->httpClient->getTraces();
        }
    }

    public function getTraces(): array
    {
        return $this->data['traces'] ?? [];
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'http_client';
    }
}
