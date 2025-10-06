<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Api;

use Logiscenter\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Complete Repository Pattern Implementation
 *
 * Addresses the "Incomplete Repository Pattern" weakness from current implementation
 */
interface ImmutableQuoteRepositoryInterface
{
    /**
     * Save immutable quote
     *
     * @param ImmutableQuoteInterface $immutableQuote
     * @return ImmutableQuoteInterface
     * @throws CouldNotSaveException
     */
    public function save(ImmutableQuoteInterface $immutableQuote): ImmutableQuoteInterface;

    /**
     * Get immutable quote by ID
     *
     * @param int $quoteId
     * @return ImmutableQuoteInterface
     * @throws NoSuchEntityException
     */
    public function getByQuoteId(int $quoteId): ImmutableQuoteInterface;

    /**
     * Get list of immutable quotes
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface;

    /**
     * Delete immutable quote
     *
     * @param ImmutableQuoteInterface $immutableQuote
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(ImmutableQuoteInterface $immutableQuote): bool;

    /**
     * Delete immutable quote by ID
     *
     * @param int $quoteId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteByQuoteId(int $quoteId): bool;

    /**
     * Get customer's immutable quotes
     *
     * @param int $customerId
     * @param bool $enabledOnly
     * @return ImmutableQuoteInterface[]
     */
    public function getByCustomerId(int $customerId, bool $enabledOnly = false): array;

    /**
     * Get customer's active immutable quote
     *
     * @param int $customerId
     * @return ImmutableQuoteInterface|null
     */
    public function getActiveByCustomerId(int $customerId): ?ImmutableQuoteInterface;

    /**
     * Check if quote is immutable
     *
     * @param int $quoteId
     * @return bool
     */
    public function isImmutable(int $quoteId): bool;

    /**
     * Bulk disable quotes for customer (when enabling a new one)
     *
     * @param int $customerId
     * @param int $excludeQuoteId
     * @return int Number of quotes disabled
     */
    public function bulkDisableForCustomer(int $customerId, int $excludeQuoteId = 0): int;
}
