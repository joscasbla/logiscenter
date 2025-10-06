<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model;

use Logiscenter\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Logiscenter\ImmutableQuote\Api\Data\ImmutableQuoteInterfaceFactory;
use Logiscenter\ImmutableQuote\Api\ImmutableQuoteManagementInterface;
use Logiscenter\ImmutableQuote\Api\ImmutableQuoteRepositoryInterface;
use Logiscenter\ImmutableQuote\Model\Event\ImmutableQuoteCreatedEvent;
use Logiscenter\ImmutableQuote\Model\Event\ImmutableQuoteEnabledEvent;
use Logiscenter\ImmutableQuote\Model\Event\ImmutableQuoteModificationAttemptedEvent;
use Logiscenter\ImmutableQuote\Model\Service\QuoteValidationService;
use Logiscenter\ImmutableQuote\Model\Service\AuditLoggerService;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Quote\Model\QuoteManagement;
use Psr\Log\LoggerInterface;

/**
 * Immutable Quote Management Service
 * 
 * High-level business operations with event-driven architecture
 */
class ImmutableQuoteManagement implements ImmutableQuoteManagementInterface
{
    /**
     * @var ImmutableQuoteRepositoryInterface
     */
    private ImmutableQuoteRepositoryInterface $immutableQuoteRepository;

    /**
     * @var ImmutableQuoteInterfaceFactory
     */
    private ImmutableQuoteInterfaceFactory $immutableQuoteFactory;

    /**
     * @var CartRepositoryInterface
     */
    private CartRepositoryInterface $cartRepository;

    /**
     * @var QuoteValidationService
     */
    private QuoteValidationService $quoteValidationService;

    /**
     * @var EventManagerInterface
     */
    private EventManagerInterface $eventManager;

    /**
     * @var AuditLoggerService
     */
    private AuditLoggerService $auditLogger;

    /**
     * @var QuoteManagement
     */
    private QuoteManagement $quoteManagement;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Constructor
     */
    public function __construct(
        ImmutableQuoteRepositoryInterface $immutableQuoteRepository,
        ImmutableQuoteInterfaceFactory $immutableQuoteFactory,
        CartRepositoryInterface $cartRepository,
        QuoteValidationService $quoteValidationService,
        EventManagerInterface $eventManager,
        AuditLoggerService $auditLogger,
        QuoteManagement $quoteManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger
    ) {
        $this->immutableQuoteRepository = $immutableQuoteRepository;
        $this->immutableQuoteFactory = $immutableQuoteFactory;
        $this->cartRepository = $cartRepository;
        $this->quoteValidationService = $quoteValidationService;
        $this->eventManager = $eventManager;
        $this->auditLogger = $auditLogger;
        $this->quoteManagement = $quoteManagement;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function createFromQuote(
        int $quoteId,
        ?int $adminUserId = null,
        array $metadata = []
    ): ImmutableQuoteInterface {
        try {
            // Validate quote exists and is valid
            $quote = $this->cartRepository->get($quoteId);
            $validationResult = $this->quoteValidationService->validateForImmutability($quote);
            
            if (!$validationResult['valid']) {
                throw new LocalizedException(
                    __('Quote cannot be made immutable: %1', implode(', ', $validationResult['errors']))
                );
            }

            // Check if already immutable
            if ($this->immutableQuoteRepository->isImmutable($quoteId)) {
                throw new LocalizedException(__('Quote %1 is already immutable', $quoteId));
            }

            // Create immutable quote
            $immutableQuote = $this->immutableQuoteFactory->create();
            $immutableQuote->setQuoteId($quoteId)
                          ->setIsImmutable(true)
                          ->setCreatedByAdminId($adminUserId)
                          ->setMetadata($metadata);

            // Save
            $immutableQuote = $this->immutableQuoteRepository->save($immutableQuote);

            // Dispatch event
            $event = new ImmutableQuoteCreatedEvent(
                $quoteId,
                $quote->getCustomerId(),
                $adminUserId,
                new \DateTime(),
                $this->auditLogger->getCurrentContext()
            );
            $this->eventManager->dispatch('logiscenter_immutable_quote_created', ['event' => $event]);

            // Audit log
            $this->auditLogger->logAction('create_immutable_quote', [
                'quote_id' => $quoteId,
                'admin_user_id' => $adminUserId,
                'customer_id' => $quote->getCustomerId(),
                'result' => 'success',
                'metadata' => $metadata
            ]);

            return $immutableQuote;

        } catch (\Exception $exception) {
            $this->auditLogger->logAction('create_immutable_quote', [
                'quote_id' => $quoteId,
                'admin_user_id' => $adminUserId,
                'result' => 'failed',
                'error' => $exception->getMessage()
            ]);

            $this->logger->error('Failed to create immutable quote', [
                'quote_id' => $quoteId,
                'admin_user_id' => $adminUserId,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            throw new LocalizedException(
                __('Could not create immutable quote: %1', $exception->getMessage()),
                $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function enableQuote(int $quoteId, int $customerId): ImmutableQuoteInterface
    {
        try {
            // Get immutable quote
            $immutableQuote = $this->immutableQuoteRepository->getByQuoteId($quoteId);
            
            // Validate quote belongs to customer
            $quote = $this->cartRepository->get($quoteId);
            if ($quote->getCustomerId() != $customerId) {
                throw new LocalizedException(__('Quote does not belong to customer'));
            }

            // Validate not already enabled
            if ($immutableQuote->isEnabled()) {
                throw new LocalizedException(__('Quote %1 is already enabled', $quoteId));
            }

            // Validate not expired
            if ($immutableQuote->isExpired()) {
                throw new LocalizedException(__('Quote %1 has expired', $quoteId));
            }

            // Disable other active quotes for customer (business rule)
            $this->immutableQuoteRepository->bulkDisableForCustomer($customerId, $quoteId);

            // Enable this quote
            $immutableQuote->enable($customerId);
            $immutableQuote = $this->immutableQuoteRepository->save($immutableQuote);

            // Dispatch event
            $event = new ImmutableQuoteEnabledEvent(
                $quoteId,
                $customerId,
                new \DateTime(),
                $this->auditLogger->getCurrentContext()
            );
            $this->eventManager->dispatch('logiscenter_immutable_quote_enabled', ['event' => $event]);

            // Audit log
            $this->auditLogger->logAction('enable_immutable_quote', [
                'quote_id' => $quoteId,
                'customer_id' => $customerId,
                'result' => 'success'
            ]);

            return $immutableQuote;

        } catch (\Exception $exception) {
            $this->auditLogger->logAction('enable_immutable_quote', [
                'quote_id' => $quoteId,
                'customer_id' => $customerId,
                'result' => 'failed',
                'error' => $exception->getMessage()
            ]);

            throw new LocalizedException(
                __('Could not enable immutable quote: %1', $exception->getMessage()),
                $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function disableQuote(int $quoteId): bool
    {
        try {
            $immutableQuote = $this->immutableQuoteRepository->getByQuoteId($quoteId);
            
            if (!$immutableQuote->isEnabled()) {
                return true; // Already disabled
            }

            $customerId = $immutableQuote->getEnabledByCustomerId();
            
            // Clear enabled fields
            $immutableQuote->setEnabledAt(null)
                          ->setEnabledByCustomerId(null);
            
            $this->immutableQuoteRepository->save($immutableQuote);

            // Audit log
            $this->auditLogger->logAction('disable_immutable_quote', [
                'quote_id' => $quoteId,
                'customer_id' => $customerId,
                'result' => 'success'
            ]);

            return true;

        } catch (\Exception $exception) {
            $this->auditLogger->logAction('disable_immutable_quote', [
                'quote_id' => $quoteId,
                'result' => 'failed',
                'error' => $exception->getMessage()
            ]);

            throw new LocalizedException(
                __('Could not disable immutable quote: %1', $exception->getMessage()),
                $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function addItems(int $quoteId, array $items): ImmutableQuoteInterface
    {
        try {
            $immutableQuote = $this->immutableQuoteRepository->getByQuoteId($quoteId);
            
            if ($immutableQuote->isImmutable()) {
                // Dispatch modification attempted event
                $event = new ImmutableQuoteModificationAttemptedEvent(
                    $quoteId,
                    0, // Unknown customer at this point
                    'add_items',
                    $items,
                    'Quote is immutable',
                    new \DateTime()
                );
                $this->eventManager->dispatch('logiscenter_immutable_quote_modification_attempted', ['event' => $event]);

                throw new LocalizedException(__('Cannot add items to immutable quote %1', $quoteId));
            }

            // Quote is not yet immutable, allow modification
            $quote = $this->cartRepository->get($quoteId);
            
            foreach ($items as $item) {
                // Add item logic here
                // This is simplified - actual implementation would use CartItemInterface
                $quote->addProduct($item['product'], $item['qty'] ?? 1);
            }

            $this->cartRepository->save($quote);

            return $immutableQuote;

        } catch (\Exception $exception) {
            $this->logger->error('Failed to add items to quote', [
                'quote_id' => $quoteId,
                'items' => $items,
                'error' => $exception->getMessage()
            ]);

            throw new LocalizedException(
                __('Could not add items to quote: %1', $exception->getMessage()),
                $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function validateForImmutability(int $quoteId): array
    {
        try {
            $quote = $this->cartRepository->get($quoteId);
            return $this->quoteValidationService->validateForImmutability($quote);
        } catch (NoSuchEntityException $exception) {
            return [
                'valid' => false,
                'errors' => ['Quote does not exist']
            ];
        } catch (\Exception $exception) {
            return [
                'valid' => false,
                'errors' => ['Validation failed: ' . $exception->getMessage()]
            ];
        }
    }

    /**
     * @inheritDoc
     */
    public function getImmutableQuoteWithData(int $quoteId): array
    {
        try {
            $immutableQuote = $this->immutableQuoteRepository->getByQuoteId($quoteId);
            $quote = $this->cartRepository->get($quoteId);

            return [
                'immutable_quote' => $immutableQuote->getData(),
                'quote' => [
                    'entity_id' => $quote->getId(),
                    'customer_id' => $quote->getCustomerId(),
                    'items_count' => $quote->getItemsCount(),
                    'items_qty' => $quote->getItemsQty(),
                    'grand_total' => $quote->getGrandTotal(),
                    'base_grand_total' => $quote->getBaseGrandTotal(),
                    'currency_code' => $quote->getQuoteCurrencyCode(),
                    'store_id' => $quote->getStoreId(),
                    'created_at' => $quote->getCreatedAt(),
                    'updated_at' => $quote->getUpdatedAt()
                ],
                'is_immutable' => $immutableQuote->isImmutable(),
                'is_enabled' => $immutableQuote->isEnabled(),
                'is_expired' => $immutableQuote->isExpired()
            ];

        } catch (\Exception $exception) {
            throw new LocalizedException(
                __('Could not get immutable quote data: %1', $exception->getMessage()),
                $exception
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function convertToOrder(int $quoteId, array $paymentData = []): int
    {
        try {
            $immutableQuote = $this->immutableQuoteRepository->getByQuoteId($quoteId);
            $quote = $this->cartRepository->get($quoteId);

            // Validate quote is enabled and immutable
            if (!$immutableQuote->isImmutable()) {
                throw new LocalizedException(__('Quote %1 is not immutable', $quoteId));
            }

            if (!$immutableQuote->isEnabled()) {
                throw new LocalizedException(__('Quote %1 is not enabled', $quoteId));
            }

            if ($immutableQuote->isExpired()) {
                throw new LocalizedException(__('Quote %1 has expired', $quoteId));
            }

            // Convert quote to order
            $orderId = $this->quoteManagement->placeOrder($quoteId);
            $order = $this->orderRepository->get($orderId);

            // Audit log
            $this->auditLogger->logAction('convert_to_order', [
                'quote_id' => $quoteId,
                'order_id' => $orderId,
                'customer_id' => $quote->getCustomerId(),
                'result' => 'success'
            ]);

            return $orderId;

        } catch (\Exception $exception) {
            $this->auditLogger->logAction('convert_to_order', [
                'quote_id' => $quoteId,
                'result' => 'failed',
                'error' => $exception->getMessage()
            ]);

            throw new LocalizedException(
                __('Could not convert quote to order: %1', $exception->getMessage()),
                $exception
            );
        }
    }
}
