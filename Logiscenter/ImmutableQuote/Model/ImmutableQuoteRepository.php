<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model;

use Logiscenter\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Logiscenter\ImmutableQuote\Api\Data\ImmutableQuoteInterfaceFactory;
use Logiscenter\ImmutableQuote\Api\ImmutableQuoteRepositoryInterface;
use Logiscenter\ImmutableQuote\Model\ResourceModel\ImmutableQuote as ImmutableQuoteResource;
use Logiscenter\ImmutableQuote\Model\ResourceModel\ImmutableQuote\Collection;
use Logiscenter\ImmutableQuote\Model\ResourceModel\ImmutableQuote\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

/**
 * Complete Repository Implementation
 *
 * Addresses the "Incomplete Repository Pattern" weakness
 */
class ImmutableQuoteRepository implements ImmutableQuoteRepositoryInterface
{
    /**
     * @var ImmutableQuoteResource
     */
    private ImmutableQuoteResource $resource;

    /**
     * @var ImmutableQuoteInterfaceFactory
     */
    private ImmutableQuoteInterfaceFactory $immutableQuoteFactory;

    /**
     * @var CollectionFactory
     */
    private CollectionFactory $collectionFactory;

    /**
     * @var SearchResultsInterfaceFactory
     */
    private SearchResultsInterfaceFactory $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private CollectionProcessorInterface $collectionProcessor;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var array
     */
    private array $instancesById = [];

    /**
     * Constructor
     *
     * @param ImmutableQuoteResource $resource
     * @param ImmutableQuoteInterfaceFactory $immutableQuoteFactory
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface $collectionProcessor
     * @param LoggerInterface $logger
     */
    public function __construct(
        ImmutableQuoteResource $resource,
        ImmutableQuoteInterfaceFactory $immutableQuoteFactory,
        CollectionFactory $collectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface $collectionProcessor,
        LoggerInterface $logger
    ) {
        $this->resource = $resource;
        $this->immutableQuoteFactory = $immutableQuoteFactory;
        $this->collectionFactory = $collectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function save(ImmutableQuoteInterface $immutableQuote): ImmutableQuoteInterface
    {
        try {
            $this->resource->save($immutableQuote);
            $this->instancesById[$immutableQuote->getQuoteId()] = $immutableQuote;

            $this->logger->info('Immutable quote saved successfully', [
                'quote_id' => $immutableQuote->getQuoteId(),
                'is_immutable' => $immutableQuote->isImmutable(),
                'enabled_at' => $immutableQuote->getEnabledAt()
            ]);

            return $immutableQuote;
        } catch (\Exception $exception) {
            $this->logger->error('Failed to save immutable quote', [
                'quote_id' => $immutableQuote->getQuoteId(),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            throw new CouldNotSaveException(
                __('Could not save immutable quote: %1', $exception->getMessage()),
                $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function getByQuoteId(int $quoteId): ImmutableQuoteInterface
    {
        if (isset($this->instancesById[$quoteId])) {
            return $this->instancesById[$quoteId];
        }

        $immutableQuote = $this->immutableQuoteFactory->create();
        $this->resource->load($immutableQuote, $quoteId);

        if (!$immutableQuote->getQuoteId()) {
            throw new NoSuchEntityException(
                __('Immutable quote with quote ID "%1" does not exist', $quoteId)
            );
        }

        $this->instancesById[$quoteId] = $immutableQuote;
        return $immutableQuote;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        /** @var SearchResultsInterface $searchResults */
        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        // Cache loaded items
        foreach ($collection->getItems() as $item) {
            $this->instancesById[$item->getQuoteId()] = $item;
        }

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(ImmutableQuoteInterface $immutableQuote): bool
    {
        try {
            $quoteId = $immutableQuote->getQuoteId();
            $this->resource->delete($immutableQuote);
            unset($this->instancesById[$quoteId]);

            $this->logger->info('Immutable quote deleted successfully', [
                'quote_id' => $quoteId
            ]);

            return true;
        } catch (\Exception $exception) {
            $this->logger->error('Failed to delete immutable quote', [
                'quote_id' => $immutableQuote->getQuoteId(),
                'error' => $exception->getMessage()
            ]);

            throw new CouldNotDeleteException(
                __('Could not delete immutable quote: %1', $exception->getMessage()),
                $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteByQuoteId(int $quoteId): bool
    {
        $immutableQuote = $this->getByQuoteId($quoteId);
        return $this->delete($immutableQuote);
    }

    /**
     * @inheritDoc
     */
    public function getByCustomerId(int $customerId, bool $enabledOnly = false): array
    {
        /** @var Collection $collection */
        $collection = $this->collectionFactory->create();
        $collection->addCustomerFilter($customerId)
                   ->addImmutableFilter(true);

        if ($enabledOnly) {
            $collection->addEnabledFilter(true);
        }

        $collection->addExpirationFilter(false); // Exclude expired
        $collection->addOrder('created_at', Collection::SORT_ORDER_DESC);

        return $collection->getItems();
    }

    /**
     * @inheritDoc
     */
    public function getActiveByCustomerId(int $customerId): ?ImmutableQuoteInterface
    {
        $activeQuoteData = $this->resource->getActiveByCustomerId($customerId);

        if (!$activeQuoteData) {
            return null;
        }

        if (isset($this->instancesById[$activeQuoteData['quote_id']])) {
            return $this->instancesById[$activeQuoteData['quote_id']];
        }

        $immutableQuote = $this->immutableQuoteFactory->create();
        $immutableQuote->setData($activeQuoteData);

        $this->instancesById[$activeQuoteData['quote_id']] = $immutableQuote;
        return $immutableQuote;
    }

    /**
     * @inheritDoc
     */
    public function isImmutable(int $quoteId): bool
    {
        try {
            // Check cache first
            if (isset($this->instancesById[$quoteId])) {
                return $this->instancesById[$quoteId]->isImmutable();
            }

            return $this->resource->isImmutable($quoteId);
        } catch (\Exception $exception) {
            $this->logger->warning('Failed to check if quote is immutable', [
                'quote_id' => $quoteId,
                'error' => $exception->getMessage()
            ]);
            return false;
        }
    }

    /**
     * @inheritDoc
     */
    public function bulkDisableForCustomer(int $customerId, int $excludeQuoteId = 0): int
    {
        try {
            $affectedRows = $this->resource->bulkDisableForCustomer($customerId, $excludeQuoteId);

            $this->logger->info('Bulk disabled quotes for customer', [
                'customer_id' => $customerId,
                'exclude_quote_id' => $excludeQuoteId,
                'affected_rows' => $affectedRows
            ]);

            // Clear cache for affected items
            $this->instancesById = [];

            return $affectedRows;
        } catch (\Exception $exception) {
            $this->logger->error('Failed to bulk disable quotes', [
                'customer_id' => $customerId,
                'error' => $exception->getMessage()
            ]);
            throw $exception;
        }
    }
}
