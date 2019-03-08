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

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Bundle\FrameworkBundle\HttpClient\TraceableHttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

/**
 * HttpClientDataCollector.
 *
 * @author Jérémy Romey <jeremy@free-agent.fr>
 */
class HttpClientDataCollector extends DataCollector
{
    protected $httpClient;

    public function __construct($httpClient = null)
    {
        $this->httpClient = $httpClient;
    }
    
    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        if ($this->httpClient && $this->httpClient instanceof TraceableHttpClient) {
            $this->data['traces'] = $this->httpClient->getTraces();
        }
    }
    
    public function getTraces(): array
    {
        if (!isset($this->data['traces'])) {
            return [];
        }
        
        return $this->data['traces'];
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
    public function getName()
    {
        return 'http_client';
    }
}
