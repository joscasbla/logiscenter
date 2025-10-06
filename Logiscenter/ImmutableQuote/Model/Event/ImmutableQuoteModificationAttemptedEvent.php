<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\Event;

/**
 * Immutable Quote Modification Attempted Event
 * 
 * Domain event for when someone tries to modify an immutable quote
 */
class ImmutableQuoteModificationAttemptedEvent
{
    /**
     * @var int
     */
    private int $quoteId;

    /**
     * @var int
     */
    private int $customerId;

    /**
     * @var string
     */
    private string $attemptedAction;

    /**
     * @var array
     */
    private array $requestData;

    /**
     * @var string
     */
    private string $preventionReason;

    /**
     * @var \DateTime
     */
    private \DateTime $attemptedAt;

    /**
     * Constructor
     *
     * @param int $quoteId
     * @param int $customerId
     * @param string $attemptedAction
     * @param array $requestData
     * @param string $preventionReason
     * @param \DateTime $attemptedAt
     */
    public function __construct(
        int $quoteId,
        int $customerId,
        string $attemptedAction,
        array $requestData,
        string $preventionReason,
        \DateTime $attemptedAt
    ) {
        $this->quoteId = $quoteId;
        $this->customerId = $customerId;
        $this->attemptedAction = $attemptedAction;
        $this->requestData = $requestData;
        $this->preventionReason = $preventionReason;
        $this->attemptedAt = $attemptedAt;
    }

    /**
     * Get quote ID
     *
     * @return int
     */
    public function getQuoteId(): int
    {
        return $this->quoteId;
    }

    /**
     * Get customer ID
     *
     * @return int
     */
    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    /**
     * Get attempted action
     *
     * @return string
     */
    public function getAttemptedAction(): string
    {
        return $this->attemptedAction;
    }

    /**
     * Get request data
     *
     * @return array
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }

    /**
     * Get prevention reason
     *
     * @return string
     */
    public function getPreventionReason(): string
    {
        return $this->preventionReason;
    }

    /**
     * Get attempted at
     *
     * @return \DateTime
     */
    public function getAttemptedAt(): \DateTime
    {
        return $this->attemptedAt;
    }
}
