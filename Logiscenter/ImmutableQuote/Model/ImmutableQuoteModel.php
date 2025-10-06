<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model;

use Logiscenter\ImmutableQuote\Api\Data\ImmutableQuoteInterface;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Immutable Quote Model - Rich Domain Model
 */
class ImmutableQuote extends AbstractModel implements ImmutableQuoteInterface, IdentityInterface
{
    public const CACHE_TAG = 'logiscenter_immutable_quote';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'logiscenter_immutable_quote';

    /**
     * @var DateTime
     */
    private DateTime $dateTime;

    /**
     * Constructor
     *
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param DateTime $dateTime
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource|null $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb|null $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        DateTime $dateTime,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->dateTime = $dateTime;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\ImmutableQuote::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getQuoteId()];
    }

    /**
     * @inheritDoc
     */
    public function getQuoteId(): int
    {
        return (int) $this->getData(self::QUOTE_ID);
    }

    /**
     * @inheritDoc
     */
    public function setQuoteId(int $quoteId): ImmutableQuoteInterface
    {
        return $this->setData(self::QUOTE_ID, $quoteId);
    }

    /**
     * @inheritDoc
     */
    public function isImmutable(): bool
    {
        return (bool) $this->getData(self::IS_IMMUTABLE);
    }

    /**
     * @inheritDoc
     */
    public function setIsImmutable(bool $isImmutable): ImmutableQuoteInterface
    {
        return $this->setData(self::IS_IMMUTABLE, $isImmutable);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt(): string
    {
        return (string) $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt(string $createdAt): ImmutableQuoteInterface
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getUpdatedAt(): string
    {
        return (string) $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setUpdatedAt(string $updatedAt): ImmutableQuoteInterface
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritDoc
     */
    public function getEnabledAt(): ?string
    {
        return $this->getData(self::ENABLED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setEnabledAt(?string $enabledAt): ImmutableQuoteInterface
    {
        return $this->setData(self::ENABLED_AT, $enabledAt);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedByAdminId(): ?int
    {
        $value = $this->getData(self::CREATED_BY_ADMIN_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritDoc
     */
    public function setCreatedByAdminId(?int $adminUserId): ImmutableQuoteInterface
    {
        return $this->setData(self::CREATED_BY_ADMIN_ID, $adminUserId);
    }

    /**
     * @inheritDoc
     */
    public function getEnabledByCustomerId(): ?int
    {
        $value = $this->getData(self::ENABLED_BY_CUSTOMER_ID);
        return $value !== null ? (int) $value : null;
    }

    /**
     * @inheritDoc
     */
    public function setEnabledByCustomerId(?int $customerId): ImmutableQuoteInterface
    {
        return $this->setData(self::ENABLED_BY_CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritDoc
     */
    public function getMetadata(): array
    {
        $metadata = $this->getData(self::METADATA);
        if (is_string($metadata)) {
            return json_decode($metadata, true) ?: [];
        }
        return is_array($metadata) ? $metadata : [];
    }

    /**
     * @inheritDoc
     */
    public function setMetadata(array $metadata): ImmutableQuoteInterface
    {
        return $this->setData(self::METADATA, json_encode($metadata));
    }

    /**
     * @inheritDoc
     */
    public function isEnabled(): bool
    {
        return $this->getEnabledAt() !== null;
    }

    /**
     * @inheritDoc
     */
    public function enable(int $customerId): ImmutableQuoteInterface
    {
        $this->setEnabledByCustomerId($customerId);
        $this->setEnabledAt($this->dateTime->gmtDate());
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function isExpired(): bool
    {
        $expiresAt = $this->getExpiresAt();
        if (!$expiresAt) {
            return false;
        }
        
        return strtotime($expiresAt) < $this->dateTime->gmtTimestamp();
    }

    /**
     * @inheritDoc
     */
    public function getExpiresAt(): ?string
    {
        return $this->getData(self::EXPIRES_AT);
    }

    /**
     * @inheritDoc
     */
    public function setExpiresAt(?string $expiresAt): ImmutableQuoteInterface
    {
        return $this->setData(self::EXPIRES_AT, $expiresAt);
    }

    /**
     * @inheritDoc
     */
    public function isTermsAccepted(): bool
    {
        return (bool) $this->getData(self::TERMS_ACCEPTED);
    }

    /**
     * @inheritDoc
     */
    public function setTermsAccepted(bool $termsAccepted): ImmutableQuoteInterface
    {
        return $this->setData(self::TERMS_ACCEPTED, $termsAccepted);
    }

    /**
     * @inheritDoc
     */
    public function getCustomerReference(): ?string
    {
        return $this->getData(self::CUSTOMER_REFERENCE);
    }

    /**
     * @inheritDoc
     */
    public function setCustomerReference(?string $customerReference): ImmutableQuoteInterface
    {
        return $this->setData(self::CUSTOMER_REFERENCE, $customerReference);
    }

    /**
     * @inheritDoc
     */
    public function getCustomFees(): array
    {
        $fees = $this->getData(self::CUSTOM_FEES);
        if (is_string($fees)) {
            return json_decode($fees, true) ?: [];
        }
        return is_array($fees) ? $fees : [];
    }

    /**
     * @inheritDoc
     */
    public function setCustomFees(array $customFees): ImmutableQuoteInterface
    {
        return $this->setData(self::CUSTOM_FEES, json_encode($customFees));
    }

    /**
     * @inheritDoc
     */
    public function getItemSorting(): array
    {
        $sorting = $this->getData(self::ITEM_SORTING);
        if (is_string($sorting)) {
            return json_decode($sorting, true) ?: [];
        }
        return is_array($sorting) ? $sorting : [];
    }

    /**
     * @inheritDoc
     */
    public function setItemSorting(array $itemSorting): ImmutableQuoteInterface
    {
        return $this->setData(self::ITEM_SORTING, json_encode($itemSorting));
    }

    /**
     * Validate business rules before save
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function beforeSave(): AbstractModel
    {
        // Business rule: Cannot disable immutability once enabled
        if ($this->getOrigData(self::IS_IMMUTABLE) && !$this->isImmutable()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Cannot disable immutability once enabled for quote %1', $this->getQuoteId())
            );
        }

        // Business rule: Cannot change enabled_at to null once set
        if ($this->getOrigData(self::ENABLED_AT) && !$this->getEnabledAt()) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Cannot disable quote once enabled for quote %1', $this->getQuoteId())
            );
        }

        return parent::beforeSave();
    }
}
