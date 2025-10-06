<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Api\Data;

/**
 * Immutable Quote Interface
 *
 * Represents an immutable quote with rich domain model
 */
interface ImmutableQuoteInterface
{
    public const QUOTE_ID = 'quote_id';
    public const IS_IMMUTABLE = 'is_immutable';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';
    public const ENABLED_AT = 'enabled_at';
    public const CREATED_BY_ADMIN_ID = 'created_by_admin_id';
    public const ENABLED_BY_CUSTOMER_ID = 'enabled_by_customer_id';
    public const METADATA = 'metadata';
    public const EXPIRES_AT = 'expires_at';
    public const TERMS_ACCEPTED = 'terms_accepted';
    public const CUSTOMER_REFERENCE = 'customer_reference';
    public const CUSTOM_FEES = 'custom_fees';
    public const ITEM_SORTING = 'item_sorting';

    /**
     * Get Quote ID
     *
     * @return int
     */
    public function getQuoteId(): int;

    /**
     * Set Quote ID
     *
     * @param int $quoteId
     * @return ImmutableQuoteInterface
     */
    public function setQuoteId(int $quoteId): ImmutableQuoteInterface;

    /**
     * Check if quote is immutable
     *
     * @return bool
     */
    public function isImmutable(): bool;

    /**
     * Set immutable status
     *
     * @param bool $isImmutable
     * @return ImmutableQuoteInterface
     */
    public function setIsImmutable(bool $isImmutable): ImmutableQuoteInterface;

    /**
     * Get created at timestamp
     *
     * @return string
     */
    public function getCreatedAt(): string;

    /**
     * Set created at timestamp
     *
     * @param string $createdAt
     * @return ImmutableQuoteInterface
     */
    public function setCreatedAt(string $createdAt): ImmutableQuoteInterface;

    /**
     * Get updated at timestamp
     *
     * @return string
     */
    public function getUpdatedAt(): string;

    /**
     * Set updated at timestamp
     *
     * @param string $updatedAt
     * @return ImmutableQuoteInterface
     */
    public function setUpdatedAt(string $updatedAt): ImmutableQuoteInterface;

    /**
     * Get enabled at timestamp
     *
     * @return string|null
     */
    public function getEnabledAt(): ?string;

    /**
     * Set enabled at timestamp
     *
     * @param string|null $enabledAt
     * @return ImmutableQuoteInterface
     */
    public function setEnabledAt(?string $enabledAt): ImmutableQuoteInterface;

    /**
     * Get admin user ID who created the quote
     *
     * @return int|null
     */
    public function getCreatedByAdminId(): ?int;

    /**
     * Set admin user ID who created the quote
     *
     * @param int|null $adminUserId
     * @return ImmutableQuoteInterface
     */
    public function setCreatedByAdminId(?int $adminUserId): ImmutableQuoteInterface;

    /**
     * Get customer ID who enabled the quote
     *
     * @return int|null
     */
    public function getEnabledByCustomerId(): ?int;

    /**
     * Set customer ID who enabled the quote
     *
     * @param int|null $customerId
     * @return ImmutableQuoteInterface
     */
    public function setEnabledByCustomerId(?int $customerId): ImmutableQuoteInterface;

    /**
     * Get metadata
     *
     * @return array
     */
    public function getMetadata(): array;

    /**
     * Set metadata
     *
     * @param array $metadata
     * @return ImmutableQuoteInterface
     */
    public function setMetadata(array $metadata): ImmutableQuoteInterface;

    /**
     * Check if quote is enabled (activated by customer)
     *
     * @return bool
     */
    public function isEnabled(): bool;

    /**
     * Enable the quote
     *
     * @param int $customerId
     * @return ImmutableQuoteInterface
     */
    public function enable(int $customerId): ImmutableQuoteInterface;

    /**
     * Check if quote is expired
     *
     * @return bool
     */
    public function isExpired(): bool;

    /**
     * Get expiration date
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string;

    /**
     * Set expiration date
     *
     * @param string|null $expiresAt
     * @return ImmutableQuoteInterface
     */
    public function setExpiresAt(?string $expiresAt): ImmutableQuoteInterface;

    /**
     * Check if terms are accepted
     *
     * @return bool
     */
    public function isTermsAccepted(): bool;

    /**
     * Set terms accepted status
     *
     * @param bool $termsAccepted
     * @return ImmutableQuoteInterface
     */
    public function setTermsAccepted(bool $termsAccepted): ImmutableQuoteInterface;

    /**
     * Get customer reference
     *
     * @return string|null
     */
    public function getCustomerReference(): ?string;

    /**
     * Set customer reference
     *
     * @param string|null $customerReference
     * @return ImmutableQuoteInterface
     */
    public function setCustomerReference(?string $customerReference): ImmutableQuoteInterface;

    /**
     * Get custom fees
     *
     * @return array
     */
    public function getCustomFees(): array;

    /**
     * Set custom fees
     *
     * @param array $customFees
     * @return ImmutableQuoteInterface
     */
    public function setCustomFees(array $customFees): ImmutableQuoteInterface;

    /**
     * Get item sorting configuration
     *
     * @return array
     */
    public function getItemSorting(): array;

    /**
     * Set item sorting configuration
     *
     * @param array $itemSorting
     * @return ImmutableQuoteInterface
     */
    public function setItemSorting(array $itemSorting): ImmutableQuoteInterface;
}
