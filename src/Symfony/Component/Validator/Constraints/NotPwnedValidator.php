<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Validator\Constraints;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Checks if a password has been leaked in a data breach using haveibeenpwned.com's API.
 * Use a k-anonymity model to protect the password being searched for.
 *
 * @see https://haveibeenpwned.com/API/v2#SearchingPwnedPasswordsByRange
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
class NotPwnedValidator extends ConstraintValidator
{
    private const RANGE_API = 'https://api.pwnedpasswords.com/range/%s';

    private $httpClient;

    public function __construct(HttpClientInterface $httpClient = null)
    {
        if (null === $httpClient && !class_exists(HttpClient::class)) {
            throw new \LogicException(sprintf('The "%s" class requires the "HttpClient" component. Try running "composer require symfony/http-client".', self::class));
        }

        $this->httpClient = $httpClient ?? HttpClient::create();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ExceptionInterface
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof NotPwned) {
            throw new UnexpectedTypeException($constraint, NotPwned::class);
        }

        if (null !== $value && !is_scalar($value) && !(\is_object($value) && method_exists($value, '__toString'))) {
            throw new UnexpectedTypeException($value, 'string');
        }

        $value = (string) $value;
        if ('' === $value) {
            return;
        }

        $hash = strtoupper(sha1($value));
        $hashPrefix = substr($hash, 0, 5);
        $url = sprintf(self::RANGE_API, $hashPrefix);

        try {
            $result = $this->httpClient->request('GET', $url)->getContent();
        } catch (ExceptionInterface $e) {
            if ($constraint->skipOnError) {
                return;
            }

            throw $e;
        }

        foreach (explode("\r\n", $result) as $line) {
            list($hashSuffix, $count) = explode(':', $line);

            if ($hashPrefix.$hashSuffix === $hash && $constraint->threshold <= (int) $count) {
                $this->context->buildViolation($constraint->message)
                    ->setCode(NotPwned::PWNED_ERROR)
                    ->addViolation();

                return;
            }
        }
    }
}
