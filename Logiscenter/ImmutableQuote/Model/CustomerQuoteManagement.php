<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model;

use Logiscenter\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Logiscenter\ImmutableQuote\Api\ImmutableQuoteManagementInterface;
use Logiscenter\ImmutableQuote\Api\ImmutableQuoteRepositoryInterface;
use Logiscenter\ImmutableQuote\Model\Service\RateLimitingService;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\AuthenticationException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Customer Quote Management
 *
 * Customer-facing API operations with authentication and rate limiting
 */
class CustomerQuoteManagement
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
     * @var RateLimitingService
     */
    private RateLimitingService $rateLimitingService;

    /**
     * Constructor
     */
    public function __construct(
        ImmutableQuoteRepositoryInterface $immutableQuoteRepository,
        ImmutableQuoteManagementInterface $immutableQuoteManagement,
        CustomerSession $customerSession,
        RateLimitingService $rateLimitingService
    ) {
        $this->immutableQuoteRepository = $immutableQuoteRepository;
        $this->immutableQuoteManagement = $immutableQuoteManagement;
        $this->customerSession = $customerSession;
        $this->rateLimitingService = $rateLimitingService;
    }

    /**
     * Get customer's immutable quotes
     *
     * @return ImmutableQuoteInterface[]
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function getMyQuotes(): array
    {
        $customerId = $this->getAuthenticatedCustomerId();

        // Rate limiting
        $this->rateLimitingService->checkRateLimit('list_quotes', $customerId);

        return $this->immutableQuoteRepository->getByCustomerId($customerId);
    }

    /**
     * Enable customer's immutable quote
     *
     * @param int $quoteId
     * @return ImmutableQuoteInterface
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function enableMyQuote(int $quoteId): ImmutableQuoteInterface
    {
        $customerId = $this->getAuthenticatedCustomerId();

        // Rate limiting
        $this->rateLimitingService->checkRateLimit('enable_quote', $customerId);

        // Validate quote belongs to customer
        $this->validateQuoteOwnership($quoteId, $customerId);

        return $this->immutableQuoteManagement->enableQuote($quoteId, $customerId);
    }

    /**
     * Get customer's specific immutable quote
     *
     * @param int $quoteId
     * @return array
     * @throws AuthenticationException
     * @throws LocalizedException
     */
    public function getMyQuote(int $quoteId): array
    {
        $customerId = $this->getAuthenticatedCustomerId();

        // Rate limiting
        $this->rateLimitingService->checkRateLimit('get_quote', $customerId);

        // Validate quote belongs to customer
        $this->validateQuoteOwnership($quoteId, $customerId);

        return $this->immutableQuoteManagement->getImmutableQuoteWithData($quoteId);
    }

    /**
     * Get authenticated customer ID
     *
     * @return int
     * @throws AuthenticationException
     */
    private function getAuthenticatedCustomerId(): int
    {
        if (!$this->customerSession->isLoggedIn()) {
            throw new AuthenticationException(__('Customer must be authenticated'));
        }

        return (int) $this->customerSession->getCustomerId();
    }

    /**
     * Validate quote belongs to customer
     *
     * @param int $quoteId
     * @param int $customerId
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    private function validateQuoteOwnership(int $quoteId, int $customerId): void
    {
        try {
            $immutableQuote = $this->immutableQuoteRepository->getByQuoteId($quoteId);
            $quoteData = $this->immutableQuoteManagement->getImmutableQuoteWithData($quoteId);

            if ($quoteData['quote']['customer_id'] != $customerId) {
                throw new LocalizedException(__('Quote does not belong to customer'));
            }
        } catch (NoSuchEntityException $exception) {
            throw new NoSuchEntityException(__('Quote not found'));
        }
    }
}
