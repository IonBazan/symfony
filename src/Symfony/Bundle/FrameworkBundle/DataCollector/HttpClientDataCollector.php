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
use Symfony\Component\VarDumper\Caster\CutStub;
use Symfony\Component\VarDumper\Cloner\ClonerInterface;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Jérémy Romey <jeremy@free-agent.fr>
 */
class HttpClientDataCollector extends DataCollector
{
    protected $httpClient;

    /**
     * @var ClonerInterface
     */
    private $cloner;

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
            foreach ($this->data['traces'] as $key => $trace) {
                $this->data['traces'][$key]['request']['options'] = $this->cloneVar($trace['request']['options']);
            }
        }
    }

    /**
     * Converts the variable into a serializable Data instance.
     *
     * This array can be displayed in the template using
     * the VarDumper component.
     *
     * @param mixed $var
     *
     * @return Data
     */
    protected function cloneVar($var)
    {
        if ($var instanceof Data) {
            return $var;
        }
        if (null === $this->cloner) {
            if (!class_exists(CutStub::class)) {
                throw new \LogicException(sprintf('The VarDumper component is needed for the %s() method. Install symfony/var-dumper version 3.4 or above.', __METHOD__));
            }
            $this->cloner = new VarCloner();
            $this->cloner->setMaxItems(-1);
            $this->cloner->addCasters($this->getCasters());
        }

        return $this->cloner->cloneVar($var);
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
