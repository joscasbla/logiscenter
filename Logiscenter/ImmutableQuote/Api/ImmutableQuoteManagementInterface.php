<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Api;

use Logiscenter\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Magento\Framework\Exception\LocalizedException;

/**
 * Immutable Quote Management Interface
 *
 * High-level business operations
 */
interface ImmutableQuoteManagementInterface
{
    /**
     * Create immutable quote from existing quote
     *
     * @param int $quoteId
     * @param int|null $adminUserId
     * @param array $metadata
     * @return ImmutableQuoteInterface
     * @throws LocalizedException
     */
    public function createFromQuote(
        int $quoteId,
        ?int $adminUserId = null,
        array $metadata = []
    ): ImmutableQuoteInterface;

    /**
     * Enable immutable quote for customer
     *
     * @param int $quoteId
     * @param int $customerId
     * @return ImmutableQuoteInterface
     * @throws LocalizedException
     */
    public function enableQuote(int $quoteId, int $customerId): ImmutableQuoteInterface;

    /**
     * Disable immutable quote
     *
     * @param int $quoteId
     * @return bool
     * @throws LocalizedException
     */
    public function disableQuote(int $quoteId): bool;

    /**
     * Add items to immutable quote (only before making immutable)
     *
     * @param int $quoteId
     * @param array $items
     * @return ImmutableQuoteInterface
     * @throws LocalizedException
     */
    public function addItems(int $quoteId, array $items): ImmutableQuoteInterface;

    /**
     * Validate if quote can be made immutable
     *
     * @param int $quoteId
     * @return array Validation result with errors if any
     */
    public function validateForImmutability(int $quoteId): array;

    /**
     * Get immutable quote with merged quote data
     *
     * @param int $quoteId
     * @return array Combined immutable quote + quote data
     * @throws LocalizedException
     */
    public function getImmutableQuoteWithData(int $quoteId): array;

    /**
     * Convert immutable quote to order
     *
     * @param int $quoteId
     * @param array $paymentData
     * @return int Order ID
     * @throws LocalizedException
     */
    public function convertToOrder(int $quoteId, array $paymentData = []): int;
}
