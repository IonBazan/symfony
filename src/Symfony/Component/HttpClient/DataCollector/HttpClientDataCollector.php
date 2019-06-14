<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient\DataCollector;

use Symfony\Component\HttpClient\TraceableHttpClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Jérémy Romey <jeremy@free-agent.fr>
 */
class HttpClientDataCollector extends DataCollector
{
    /**
     * @var TraceableHttpClient[]
     */
    protected $clients = [];

    public function addClient(string $name, HttpClientInterface $client)
    {
        if ($client) {
            $this->clients[$name] = $client;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data['clients'] = [];
        foreach ($this->clients as $name => $client) {
            $clientTraces = $client instanceof TraceableHttpClient ? $client->getTraces() : [];

            foreach ($clientTraces as $key => $trace) {
                $clientTraces[$key]['request']['options'] = $this->cloneVar($trace['request']['options']);
            }

            $this->data['clients'][$name] = [
                'traces' => $clientTraces,
                'stats' => $this->calculateStatistics($clientTraces),
            ];
        }
        $this->data['total'] = $this->calculateTotalStatistics();
    }

    public function getClients(): array
    {
        return $this->data['clients'];
    }

    public function getRequestCount(): int
    {
        return $this->data['total']['traces'];
    }

    public function getErrors(): int
    {
        return $this->data['total']['errors'];
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

    private function calculateStatistics(array $traces)
    {
        $stats = [
            'errors' => 0,
        ];

        foreach ($traces as $trace) {
            if (400 <= $trace['response']['statusCode']) {
                ++$stats['errors'];
            }
        }

        return $stats;
    }

    private function calculateTotalStatistics(): array
    {
        $totals = [
            'traces' => 0,
            'errors' => 0,
        ];

        foreach ($this->getClients() as $client) {
            $totals['traces'] += \count($client['traces']);
            $totals['errors'] += $client['stats']['errors'];
        }

        return $totals;
    }
}
