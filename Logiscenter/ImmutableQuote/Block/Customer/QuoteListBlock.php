<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Block\Customer;

use Logiscenter\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Logiscenter\ImmutableQuote\Api\ImmutableQuoteManagementInterface;
use Logiscenter\ImmutableQuote\Api\ImmutableQuoteRepositoryInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Phrase;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Customer Quote List Block
 */
class QuoteListBlock extends Template
{
    /**
     * @var ImmutableQuoteRepositoryInterface
     */
    private ImmutableQuoteRepositoryInterface $immutableQuoteRepository;

    /**
     * @var ImmutableQuoteManagementInterface
     */
    private ImmutableQuoteManagementInterface $immutableQuoteManagement;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var PricingHelper
     */
    private PricingHelper $pricingHelper;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;

    /**
     * @var array
     */
    private ?array $customerQuotes = null;

    /**
     * Constructor
     */
    public function __construct(
        Context $context,
        ImmutableQuoteRepositoryInterface $immutableQuoteRepository,
        ImmutableQuoteManagementInterface $immutableQuoteManagement,
        CustomerSession $customerSession,
        PricingHelper $pricingHelper,
        TimezoneInterface $timezone,
        array $data = []
    ) {
        $this->immutableQuoteRepository = $immutableQuoteRepository;
        $this->immutableQuoteManagement = $immutableQuoteManagement;
        $this->customerSession = $customerSession;
        $this->pricingHelper = $pricingHelper;
        $this->timezone = $timezone;
        parent::__construct($context, $data);
    }

    /**
     * Get customer's immutable quotes
     *
     * @return ImmutableQuoteInterface[]
     */
    public function getCustomerImmutableQuotes(): array
    {
        if ($this->customerQuotes === null) {
            $customerId = (int) $this->customerSession->getCustomerId();
            $this->customerQuotes = $this->immutableQuoteRepository->getByCustomerId($customerId);
        }

        return $this->customerQuotes;
    }

    /**
     * Get quote data with merged quote information
     *
     * @param int $quoteId
     * @return array
     */
    public function getQuoteData(int $quoteId): array
    {
        try {
            $data = $this->immutableQuoteManagement->getImmutableQuoteWithData($quoteId);
            return $data['quote'] ?? [];
        } catch (\Exception $exception) {
            return [];
        }
    }

    /**
     * Format price
     *
     * @param float $price
     * @return string
     */
    public function formatPrice(float $price): string
    {
        return $this->pricingHelper->currency($price, true, false);
    }

    /**
     * Format date
     *
     * @param string $date
     * @return string
     */
    public function formatDate(?string $date): string
    {
        if (!$date) {
            return '';
        }

        try {
            // Convert string to DateTime object first
            $dateTime = new \DateTime($date);
            // Use timezone to format the date properly
            return $this->timezone->formatDateTime(
                $dateTime,
                \IntlDateFormatter::MEDIUM,
                \IntlDateFormatter::NONE,
                null,
                null,
                'dd/MM/yyyy'
            );
        } catch (\Exception $e) {
            // Fallback to original date if formatting fails
            return $date;
        }
    }

    /**
     * Get status label
     *
     * @param ImmutableQuoteInterface $quote
     * @return string
     */
    public function getStatusLabel(ImmutableQuoteInterface $quote): Phrase
    {
        if ($quote->isExpired()) {
            return __('Expired');
        }

        if ($quote->isEnabled()) {
            return __('Active');
        }

        return __('Inactive');
    }

    /**
     * Get status CSS class
     *
     * @param ImmutableQuoteInterface $quote
     * @return string
     */
    public function getStatusClass(ImmutableQuoteInterface $quote): string
    {
        if ($quote->isExpired()) {
            return 'status-expired';
        }

        if ($quote->isEnabled()) {
            return 'status-active';
        }

        return 'status-inactive';
    }

    /**
     * Get view URL
     *
     * @param int $quoteId
     * @return string
     */
    public function getViewUrl(int $quoteId): string
    {
        return $this->getUrl('immutable-quotes/customer/view', ['quote_id' => $quoteId]);
    }

    /**
     * Get enable URL
     *
     * @return string
     */
    public function getEnableUrl(): string
    {
        return $this->getUrl('immutable-quotes/customer/enable');
    }

    /**
     * Get checkout URL
     *
     * @param int $quoteId
     * @return string
     */
    public function getCheckoutUrl(int $quoteId): string
    {
        return $this->getUrl('checkout', ['quote_id' => $quoteId]);
    }
}
