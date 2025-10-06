<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\Event;

/**
 * Immutable Quote Enabled Event
 *
 * Domain event for when immutable quote is enabled/activated by customer
 */
class ImmutableQuoteEnabledEvent
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
     * @var \DateTime
     */
    private \DateTime $enabledAt;

    /**
     * @var array
     */
    private array $context;

    /**
     * Constructor
     *
     * @param int $quoteId
     * @param int $customerId
     * @param \DateTime $enabledAt
     * @param array $context
     */
    public function __construct(
        int $quoteId,
        int $customerId,
        \DateTime $enabledAt,
        array $context = []
    ) {
        $this->quoteId = $quoteId;
        $this->customerId = $customerId;
        $this->enabledAt = $enabledAt;
        $this->context = $context;
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
     * Get enabled at
     *
     * @return \DateTime
     */
    public function getEnabledAt(): \DateTime
    {
        return $this->enabledAt;
    }

    /**
     * Get context
     *
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
