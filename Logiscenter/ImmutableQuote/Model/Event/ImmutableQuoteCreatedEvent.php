<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\Event;

/**
 * Immutable Quote Created Event
 *
 * Domain event for when immutable quote is created
 */
class ImmutableQuoteCreatedEvent
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
     * @var int|null
     */
    private ?int $adminUserId;

    /**
     * @var \DateTime
     */
    private \DateTime $createdAt;

    /**
     * @var array
     */
    private array $context;

    /**
     * Constructor
     *
     * @param int $quoteId
     * @param int $customerId
     * @param int|null $adminUserId
     * @param \DateTime $createdAt
     * @param array $context
     */
    public function __construct(
        int $quoteId,
        int $customerId,
        ?int $adminUserId,
        \DateTime $createdAt,
        array $context = []
    ) {
        $this->quoteId = $quoteId;
        $this->customerId = $customerId;
        $this->adminUserId = $adminUserId;
        $this->createdAt = $createdAt;
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
     * Get admin user ID
     *
     * @return int|null
     */
    public function getAdminUserId(): ?int
    {
        return $this->adminUserId;
    }

    /**
     * Get created at
     *
     * @return \DateTime
     */
    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
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
