<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\HttpClient;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * @author Jérémy Romey <jeremy@free-agent.fr>
 */
final class TraceableHttpClient implements HttpClientInterface
{
    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var array
     */
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
        $onProgress = $options['on_progress'] ?? static function () {};
        $redirectCount = -1;
        $traceableOptions['on_progress'] = function (int $dlNow, int $dlSize, array $info) use ($method, $url, $options, $onProgress, &$redirectCount) {
            $onProgress($dlNow, $dlSize, $info);
            if ($redirectCount !== $info['redirect_count'] && 0 !== $info['http_code']) {
                $redirectCount = $info['redirect_count'];
                $this->addTrace([
                    'request' => [
                        'method' => $method,
                        'url' => $url,
                        'options' => $options,
                    ],
                    'response' => [
                        'statusCode' => $info['http_code'],
                        'headers' => $info['response_headers'],
                    ],
                ]);
            }
        };

        return $this->httpClient->request($method, $url, $traceableOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->httpClient->stream($responses, $timeout);
    }

    public function getTraces(): array
    {
        return $this->traces;
    }

    public function addTrace(array $trace): void
    {
        $this->traces[] = $trace;
    }
}
