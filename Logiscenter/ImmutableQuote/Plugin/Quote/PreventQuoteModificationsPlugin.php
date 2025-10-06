<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Plugin\Quote;

use Logiscenter\ImmutableQuote\Api\ImmutableQuoteRepositoryInterface;
use Logiscenter\ImmutableQuote\Model\Event\ImmutableQuoteModificationAttemptedEvent;
use Logiscenter\ImmutableQuote\Model\Service\AuditLoggerService;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Psr\Log\LoggerInterface;

/**
 * Unified Quote Modification Prevention Plugin
 *
 * Consolidates all prevention logic into single plugin to address
 * "Plugin Proliferation" weakness from current implementation
 */
class PreventQuoteModificationsPlugin
{
    /**
     * @var ImmutableQuoteRepositoryInterface
     */
    private ImmutableQuoteRepositoryInterface $immutableQuoteRepository;

    /**
     * @var EventManagerInterface
     */
    private EventManagerInterface $eventManager;

    /**
     * @var AuditLoggerService
     */
    private AuditLoggerService $auditLogger;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var array Cache for quote immutability status
     */
    private array $immutableStatusCache = [];

    /**
     * Constructor
     */
    public function __construct(
        ImmutableQuoteRepositoryInterface $immutableQuoteRepository,
        EventManagerInterface $eventManager,
        AuditLoggerService $auditLogger,
        LoggerInterface $logger
    ) {
        $this->immutableQuoteRepository = $immutableQuoteRepository;
        $this->eventManager = $eventManager;
        $this->auditLogger = $auditLogger;
        $this->logger = $logger;
    }

    /**
     * Prevent adding items to immutable quote
     *
     * @param Quote $subject
     * @param callable $proceed
     * @param mixed $product
     * @param null|float|int $request
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundAddProduct(Quote $subject, callable $proceed, $product, $request = null)
    {
        if ($this->isImmutableQuote($subject)) {
            $this->handleModificationAttempt($subject, 'add_product', [
                'product_id' => is_object($product) ? $product->getId() : $product,
                'request' => $request
            ]);

            throw new LocalizedException(
                __('Cannot add products to immutable quote. This quote is locked and cannot be modified.')
            );
        }

        return $proceed($product, $request);
    }

    /**
     * Prevent removing items from immutable quote
     *
     * @param Quote $subject
     * @param callable $proceed
     * @param int $itemId
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundRemoveItem(Quote $subject, callable $proceed, $itemId)
    {
        if ($this->isImmutableQuote($subject)) {
            $this->handleModificationAttempt($subject, 'remove_item', ['item_id' => $itemId]);

            throw new LocalizedException(
                __('Cannot remove items from immutable quote. This quote is locked and cannot be modified.')
            );
        }

        return $proceed($itemId);
    }

    /**
     * Prevent updating item quantities in immutable quote
     *
     * @param Quote $subject
     * @param callable $proceed
     * @param int $itemId
     * @param array $buyRequest
     * @param null|array|Varien_Object $params
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundUpdateItem(Quote $subject, callable $proceed, $itemId, $buyRequest, $params = null)
    {
        if ($this->isImmutableQuote($subject)) {
            $this->handleModificationAttempt($subject, 'update_item', [
                'item_id' => $itemId,
                'buy_request' => $buyRequest,
                'params' => $params
            ]);

            throw new LocalizedException(
                __('Cannot update items in immutable quote. This quote is locked and cannot be modified.')
            );
        }

        return $proceed($itemId, $buyRequest, $params);
    }

    /**
     * Prevent changing billing address of immutable quote
     *
     * @param Quote $subject
     * @param callable $proceed
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundSetBillingAddress(Quote $subject, callable $proceed, $address)
    {
        if ($this->isImmutableQuote($subject)) {
            $this->handleModificationAttempt($subject, 'set_billing_address', [
                'address_data' => $address ? $address->getData() : null
            ]);

            throw new LocalizedException(
                __('Cannot change billing address of immutable quote. This quote is locked and cannot be modified.')
            );
        }

        return $proceed($address);
    }

    /**
     * Prevent changing shipping address of immutable quote
     *
     * @param Quote $subject
     * @param callable $proceed
     * @param \Magento\Quote\Api\Data\AddressInterface $address
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundSetShippingAddress(Quote $subject, callable $proceed, $address)
    {
        if ($this->isImmutableQuote($subject)) {
            $this->handleModificationAttempt($subject, 'set_shipping_address', [
                'address_data' => $address ? $address->getData() : null
            ]);

            throw new LocalizedException(
                __('Cannot change shipping address of immutable quote. This quote is locked and cannot be modified.')
            );
        }

        return $proceed($address);
    }

    /**
     * Prevent applying coupons to immutable quote
     *
     * @param Quote $subject
     * @param callable $proceed
     * @param string $couponCode
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundSetCouponCode(Quote $subject, callable $proceed, $couponCode)
    {
        if ($this->isImmutableQuote($subject)) {
            $this->handleModificationAttempt($subject, 'set_coupon_code', [
                'coupon_code' => $couponCode
            ]);

            throw new LocalizedException(
                __('Cannot apply coupons to immutable quote. This quote is locked and cannot be modified.')
            );
        }

        return $proceed($couponCode);
    }

    /**
     * Prevent changing payment method of immutable quote during checkout
     * Allow only during final order placement
     *
     * @param Quote $subject
     * @param callable $proceed
     * @param \Magento\Quote\Api\Data\PaymentInterface $payment
     * @return mixed
     * @throws LocalizedException
     */
    public function aroundSetPayment(Quote $subject, callable $proceed, $payment)
    {
        if ($this->isImmutableQuote($subject)) {
            // Allow payment method during checkout process
            // This is needed for order placement
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
            $allowedMethods = [
                'placeOrder',
                'submitQuote',
                'place',
                'convertToOrder'
            ];

            $isOrderPlacement = false;
            foreach ($backtrace as $trace) {
                if (isset($trace['function']) && in_array($trace['function'], $allowedMethods)) {
                    $isOrderPlacement = true;
                    break;
                }
            }

            if (!$isOrderPlacement) {
                $this->handleModificationAttempt($subject, 'set_payment', [
                    'payment_method' => $payment ? $payment->getMethod() : null
                ]);

                throw new LocalizedException(
                    __('Cannot change payment method of immutable quote outside of checkout process.')
                );
            }
        }

        return $proceed($payment);
    }

    /**
     * Check if quote is immutable with caching
     *
     * @param CartInterface $quote
     * @return bool
     */
    private function isImmutableQuote(CartInterface $quote): bool
    {
        $quoteId = (int) $quote->getId();

        if (!$quoteId) {
            return false; // New quotes are not immutable
        }

        // Check cache first
        if (isset($this->immutableStatusCache[$quoteId])) {
            return $this->immutableStatusCache[$quoteId];
        }

        try {
            $isImmutable = $this->immutableQuoteRepository->isImmutable($quoteId);
            $this->immutableStatusCache[$quoteId] = $isImmutable;
            return $isImmutable;
        } catch (\Exception $exception) {
            $this->logger->warning('Failed to check quote immutability', [
                'quote_id' => $quoteId,
                'error' => $exception->getMessage()
            ]);
            return false; // Fail safe - allow modification if check fails
        }
    }

    /**
     * Handle modification attempt
     *
     * @param CartInterface $quote
     * @param string $action
     * @param array $requestData
     * @return void
     */
    private function handleModificationAttempt(CartInterface $quote, string $action, array $requestData): void
    {
        // Dispatch event
        $event = new ImmutableQuoteModificationAttemptedEvent(
            (int) $quote->getId(),
            (int) $quote->getCustomerId(),
            $action,
            $requestData,
            'Quote is immutable and cannot be modified',
            new \DateTime()
        );

        $this->eventManager->dispatch('logiscenter_immutable_quote_modification_attempted', [
            'event' => $event
        ]);

        // Audit log
        $this->auditLogger->logAction('modification_prevented', [
            'quote_id' => $quote->getId(),
            'customer_id' => $quote->getCustomerId(),
            'action' => $action,
            'request_data' => $requestData,
            'result' => 'blocked'
        ]);
    }
}
