<?php
declare(strict_types=1);

namespace Logiscenter\ImmutableQuote\Model\Service;

use Magento\Framework\App\CacheInterface;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Cache Service for Immutable Quotes
 *
 * Multi-layer caching strategy with invalidation
 */
class CacheServiceForImmutableQuotes
{
    private const CACHE_TAG = 'LOGISCENTER_IMMUTABLE_QUOTE';
    private const CACHE_PREFIX = 'logiscenter_immutable_quote_';

    // Cache TTLs in seconds
    private const TTL_QUOTE_STATUS = 300; // 5 minutes
    private const TTL_QUOTE_PERMISSIONS = 1800; // 30 minutes
    private const TTL_QUOTE_LIST = 120; // 2 minutes

    /**
     * @var CacheInterface
     */
    private CacheInterface $cache;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var array Request-level cache
     */
    private array $requestCache = [];

    /**
     * Constructor
     */
    public function __construct(
        CacheInterface $cache,
        SerializerInterface $serializer
    ) {
        $this->cache = $cache;
        $this->serializer = $serializer;
    }

    /**
     * Get quote status from cache
     *
     * @param int $quoteId
     * @return array|null
     */
    public function getQuoteStatus(int $quoteId): ?array
    {
        $cacheKey = self::CACHE_PREFIX . 'status_' . $quoteId;

        // Check request cache first
        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        // Check application cache
        $cached = $this->cache->load($cacheKey);
        if ($cached) {
            $data = $this->serializer->unserialize($cached);
            $this->requestCache[$cacheKey] = $data;
            return $data;
        }

        return null;
    }

    /**
     * Set quote status in cache
     *
     * @param int $quoteId
     * @param array $status
     * @return void
     */
    public function setQuoteStatus(int $quoteId, array $status): void
    {
        $cacheKey = self::CACHE_PREFIX . 'status_' . $quoteId;

        // Set in request cache
        $this->requestCache[$cacheKey] = $status;

        // Set in application cache
        $this->cache->save(
            $this->serializer->serialize($status),
            $cacheKey,
            [self::CACHE_TAG],
            self::TTL_QUOTE_STATUS
        );
    }

    /**
     * Get quote permissions from cache
     *
     * @param int $quoteId
     * @param int $customerId
     * @return array|null
     */
    public function getQuotePermissions(int $quoteId, int $customerId): ?array
    {
        $cacheKey = self::CACHE_PREFIX . 'permissions_' . $quoteId . '_' . $customerId;

        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        $cached = $this->cache->load($cacheKey);
        if ($cached) {
            $data = $this->serializer->unserialize($cached);
            $this->requestCache[$cacheKey] = $data;
            return $data;
        }

        return null;
    }

    /**
     * Set quote permissions in cache
     *
     * @param int $quoteId
     * @param int $customerId
     * @param array $permissions
     * @return void
     */
    public function setQuotePermissions(int $quoteId, int $customerId, array $permissions): void
    {
        $cacheKey = self::CACHE_PREFIX . 'permissions_' . $quoteId . '_' . $customerId;

        $this->requestCache[$cacheKey] = $permissions;

        $this->cache->save(
            $this->serializer->serialize($permissions),
            $cacheKey,
            [self::CACHE_TAG],
            self::TTL_QUOTE_PERMISSIONS
        );
    }

    /**
     * Get customer quotes list from cache
     *
     * @param int $customerId
     * @return array|null
     */
    public function getCustomerQuotesList(int $customerId): ?array
    {
        $cacheKey = self::CACHE_PREFIX . 'customer_list_' . $customerId;

        if (isset($this->requestCache[$cacheKey])) {
            return $this->requestCache[$cacheKey];
        }

        $cached = $this->cache->load($cacheKey);
        if ($cached) {
            $data = $this->serializer->unserialize($cached);
            $this->requestCache[$cacheKey] = $data;
            return $data;
        }

        return null;
    }

    /**
     * Set customer quotes list in cache
     *
     * @param int $customerId
     * @param array $quotes
     * @return void
     */
    public function setCustomerQuotesList(int $customerId, array $quotes): void
    {
        $cacheKey = self::CACHE_PREFIX . 'customer_list_' . $customerId;

        $this->requestCache[$cacheKey] = $quotes;

        $this->cache->save(
            $this->serializer->serialize($quotes),
            $cacheKey,
            [self::CACHE_TAG],
            self::TTL_QUOTE_LIST
        );
    }

    /**
     * Invalidate cache for specific quote
     *
     * @param int $quoteId
     * @return void
     */
    public function invalidateQuote(int $quoteId): void
    {
        $patterns = [
            self::CACHE_PREFIX . 'status_' . $quoteId,
            self::CACHE_PREFIX . 'permissions_' . $quoteId . '_*'
        ];

        foreach ($patterns as $pattern) {
            $this->cache->remove($pattern);
        }

        // Clear from request cache
        $this->requestCache = array_filter(
            $this->requestCache,
            function ($key) use ($quoteId) {
                return strpos($key, 'status_' . $quoteId) === false &&
                       strpos($key, 'permissions_' . $quoteId . '_') === false;
            },
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Invalidate cache for customer
     *
     * @param int $customerId
     * @return void
     */
    public function invalidateCustomer(int $customerId): void
    {
        $cacheKey = self::CACHE_PREFIX . 'customer_list_' . $customerId;
        $this->cache->remove($cacheKey);
        unset($this->requestCache[$cacheKey]);
    }

    /**
     * Clear all cache
     *
     * @return void
     */
    public function clearAll(): void
    {
        $this->cache->clean([self::CACHE_TAG]);
        $this->requestCache = [];
    }
}
