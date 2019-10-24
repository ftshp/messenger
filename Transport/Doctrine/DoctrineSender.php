<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\Messenger\Transport\Doctrine;

use Doctrine\DBAL\DBALException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Stamp\ControlledDelayStamp;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\TransportMessageIdStamp;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * @author Vincent Touzet <vincent.touzet@gmail.com>
 *
 * @experimental in 4.3
 */
class DoctrineSender implements SenderInterface
{
    private $connection;
    private $serializer;

    public function __construct(Connection $connection, SerializerInterface $serializer = null)
    {
        $this->connection = $connection;
        $this->serializer = $serializer ?? new PhpSerializer();
    }

    /**
     * {@inheritdoc}
     */
    public function send(Envelope $envelope): Envelope
    {
        $encodedMessage = $this->serializer->encode($envelope);

        /** @var DelayStamp|null $delayStamp */
        $delayStamp = $envelope->last(DelayStamp::class);
        /** @var ControlledDelayStamp|null $controlledDelayStamp */
        $controlledDelayStamp = $envelope->last(ControlledDelayStamp::class);

        $availableAt = null;

        if (null !== $delayStamp) {
            $availableAt = (new \DateTime())->modify(sprintf('+%d seconds', $delayStamp->getDelay() / 1000));
        }

        if (null === $delayStamp && null !== $controlledDelayStamp) {
            $availableAt = $controlledDelayStamp->getProcessAt();
        }

        try {
            $id = $this->connection->send($encodedMessage['body'], $encodedMessage['headers'] ?? [], $availableAt);
        } catch (DBALException $exception) {
            throw new TransportException($exception->getMessage(), 0, $exception);
        }

        return $envelope->with(new TransportMessageIdStamp($id));
    }
}
