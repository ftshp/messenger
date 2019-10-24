<?php

declare(strict_types=1);

namespace Symfony\Component\Messenger\Stamp;

/**
 * Apply this stamp to delay delivery of your message on a transport at required date and time.
 */
final class ControlledDelayStamp implements StampInterface
{
    private $processAt;

    /**
     * @param \DateTimeInterface $processAt The time at which the message is to be processed
     */
    public function __construct(\DateTimeInterface $processAt)
    {
        $this->processAt = $processAt;
    }

    public function getProcessAt(): \DateTimeInterface
    {
        return $this->processAt;
    }
}
